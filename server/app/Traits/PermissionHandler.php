<?php

namespace App\Traits;

use App\Exceptions\Exception;
use App\Library\Redis;
use App\Library\RoleType;
use App\Library\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PermissionHandler
 *
 * PermissionHandler for limit permissions 
 */
trait PermissionHandler
{
    use SessionHelper;

    /**
     * Grant permission
     * 
     * @param $permissionString permission string name
     * @param $scope requested scope ADMIN | MANAGER | EMPLOYEE
     * @param $enableContext for enable context for compute users scope
     * @param $employeeId employee id for check scope permission
     */
    public function grantPermission($permissionString, $scope = null, $enableContext = false, $employeeId = null)
    {
        try {

            $userSession = app(Session::class);

            $permission = $userSession->getPermission();

            $requestedUser = $userSession->getUser();

            if (empty($requestedUser)) {
                return $permission;
            }

            $redis = new Redis($userSession);

            // for scope base permission handling
            if (!is_null($scope)) {
                return $this->grantPermissionByScope($scope, $permissionString, $enableContext, $employeeId, $userSession, $redis);
            }

            // for global admin
            if ($userSession->isGlobalAdmin()) {
                $userRole = $redis->getUserRole($requestedUser->adminRoleId);
                // get permitted actions
                $permittedActions = isset($userRole->permittedActions) ? json_decode($userRole->permittedActions, true) : [];
                $fieldPermissions = isset($userRole->fieldPermissions) ? json_decode($userRole->fieldPermissions, true) : [];
                $scopeOdAccess = isset($userRole->customCriteria) ? json_decode($userRole->customCriteria, true) : [];

                // check permission exist
                if (in_array($permissionString, $permittedActions)) {
                    // TODO:: should get from db
                    if ($enableContext) {
                        $this->initContext($userSession, RoleType::ADMIN, $scopeOdAccess);
                    }
                    $permission->setHasPermited(true);
                    $permission->setRole(RoleType::ADMIN);
                    $permission->setFieldPermissions($fieldPermissions);
                    return $permission;
                }
            }

            $rolePriority = [RoleType::EMPLOYEE, RoleType::MANAGER, RoleType::ADMIN];

            foreach ($rolePriority as $roleType) {
                $key = strtolower($roleType) . 'RoleId';
                // get role id
                $roleId = (isset($requestedUser->$key)) ? $requestedUser->$key : null;

                // if role not exist
                if (empty($roleId)) {
                    continue;
                }

                $userRole = $redis->getUserRole($roleId);
                // get permitted actions
                $permittedActions = isset($userRole->permittedActions) ? json_decode($userRole->permittedActions, true) : [];
                $fieldPermissions = isset($userRole->fieldPermissions) ? json_decode($userRole->fieldPermissions, true) : [];
                $scopeOdAccess = isset($userRole->customCriteria) ? json_decode($userRole->customCriteria, true) : [];

                // check permission exist
                if (in_array($permissionString, $permittedActions)) {
                    if ($enableContext) {
                        $result = $this->initContext($userSession, $roleType, $scopeOdAccess, $employeeId);
                        if (!$result) { // if requested user couldn't access to given employee return as not permitted
                            return $permission;
                        }
                    }
                    $permission->setHasPermited(true);
                    $permission->setRole($roleType);
                    $permission->setFieldPermissions($fieldPermissions);
                    return $permission;
                }
            }

            return $permission;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Grant permission by requested scope
     */
    private function grantPermissionByScope($scope, $permissionString, $enableContext, $employeeId, $session, $redis)
    {
        $roleId = null;

        $requestedUser = $session->getUser();

        $permission = $session->getPermission();

        switch ($scope) {
            case RoleType::ADMIN:
                $roleId = (isset($requestedUser->adminRoleId)) ? $requestedUser->adminRoleId : null;
                break;
            case RoleType::MANAGER:
                $roleId = (isset($requestedUser->managerRoleId)) ? $requestedUser->managerRoleId : null;
                break;
            case RoleType::EMPLOYEE:
                $roleId = (isset($requestedUser->employeeRoleId)) ? $requestedUser->employeeRoleId : null;
                break;
            default:
                // when scope is invalid, send default permission object
                return $permission;
                break;
        }

        // if requested role not exist for user return default permission object
        if (empty($roleId)) {
            return $permission;
        }

        $userRole = $redis->getUserRole($roleId);
        // get permitted actions
        $permittedActions = isset($userRole->permittedActions) ? json_decode($userRole->permittedActions, true) : [];
        $fieldPermissions = isset($userRole->fieldPermissions) ? json_decode($userRole->fieldPermissions, true) : [];
        $scopeOdAccess = isset($userRole->customCriteria) ? json_decode($userRole->customCriteria, true) : [];

        // check permission exist
        if (in_array($permissionString, $permittedActions)) {
            if ($enableContext) {
                $result = $this->initContext($session, $scope, $scopeOdAccess, $employeeId);
                if (!$result) { // if requested user couldn't access to given employee return as not permitted
                    return $permission;
                }
            }
            $permission->setHasPermited(true);
            $permission->setRole($scope);
            $permission->setFieldPermissions($fieldPermissions);
            return $permission;
        }

    }

    private function initContext($userSession, $scopeLevel, $scopeOfAccess = [], $employeeId = null)
    {
        $context = $userSession->getContext();

        $user = $userSession->getUser();

        switch ($scopeLevel) {
            case RoleType::ADMIN:
                if ($userSession->isGlobalAdmin()) {
                    $employeeIds = $this->getAllEmployeeIds();
                } else {
                    $employeeIds = $this->getAdminRolePermittedEmployeeIds($user->employeeId, $scopeOfAccess);
                }
                $context->setAdminPermittedEmployeeIds($employeeIds);
                break;
            case RoleType::MANAGER:
                // direct employees
                $directEmployeeIds = $this->getManagerRoleDirectEmployeeIds($user->employeeId);
                $context->setDirectEmployeeIds($directEmployeeIds);

                // indirect employees
                if (isset($scopeOfAccess['manager']) && in_array('indirect', $scopeOfAccess['manager'])) {
                    $inDirectEmployeeIds = $this->getManagerRoleInDirectEmployeeIds($user->employeeId);
                    $context->setIndirectEmployeeIds($inDirectEmployeeIds);
                }
                break;
            default:
                return ($user->employeeId == $employeeId);
                break;
        }

        if (is_null($employeeId) || $employeeId == 'new') { // ignore check permission against given employee
            return true;
        }

        return $context->hasPermitted($employeeId);
    }

}