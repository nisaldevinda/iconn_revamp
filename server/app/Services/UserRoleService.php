<?php

namespace App\Services;

use App\Exceptions\Exception;
use App\Library\Redis;
use App\Library\Store;
use Illuminate\Support\Facades\Lang;
use App\Traits\JsonModelReader;
use App\Library\ModelValidator;
use App\Library\RelationshipType;
use App\Library\RoleType;
use App\Library\Session;
use Illuminate\Support\Facades\Log;

class UserRoleService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $redis;

    private $userRoleModel;

    private $frontEndDefinitionModel;

    private $session;
    private $modelService;

    public function __construct(Store $store, Redis $redis, Session $session, ModelService $modelService)
    {
        $this->store = $store;
        $this->redis = $redis;
        $this->userRoleModel = $this->getModel('userRole', true);
        $this->frontEndDefinitionModel = $this->getModel('frontEndDefinition', true);
        $this->session = $session;
        $this->modelService = $modelService;
    }

    /**
     * Function removes invalid permissions & add default permissions according to role type
     * 
     * Usage:
     * $roleType = 'ADMIN'
     * $rolePermissions = ['master-data-write']
     * 
     * Sample output:
     * ['master-data-write', 'master-data-read']
     * 
     */
    private function filterPermissions($roleType, $rolePermissions)
    {
        $permissionConfig = config('permission');

        $permissions = [];

        // if role not exist
        if (!isset($permissionConfig['rolePermissions'][$roleType])) {
            return $permissions;
        }

        // role permission data
        $rolePermisionConf = $permissionConfig['rolePermissions'][$roleType];

        // availabale permissions for given role type
        $grantedPermissions = isset($rolePermisionConf['permission-list']) ? $rolePermisionConf['permission-list'] : [];
        // availabale default permissions for given role type
        $defaultPermissions = isset($rolePermisionConf['default-permissions']) ? $rolePermisionConf['default-permissions'] : [];
        // remove invalid permissions
        $validPermissions = array_filter($rolePermissions, function ($permission) use ($grantedPermissions) {
            return (in_array($permission, $grantedPermissions));
        });

        return array_merge($validPermissions, $defaultPermissions);
    }

    /**
     * Function validate scope of access
     * 
     * Usage:
     * $roleType = 'ADMIN'
     * $rolePermissions = ['location' => [1,2,3]]
     * 
     * Sample output:
     * [
     *   'location' => [1,2]
     * ]
     * 
     */
    private function filterScopeOfAccess($roleType, $scopeOfAccess)
    {
        $queryBuilder = $this->store->getFacade();

        switch ($roleType) {
            case RoleType::ADMIN:
                $scope = [
                    'location' => []
                ];
                // validate locations
                if (isset($scopeOfAccess['location'])) {
                    $locations = $queryBuilder::table('location')->where('isDelete', 0)->pluck('id')->toArray();
                    $validLocations = array_intersect($scopeOfAccess['location'], $locations);
                    $scope['location'] = $validLocations;
                }
                break;
            case RoleType::MANAGER:
                $scope = [
                    'manager' => []
                ];
                if (isset($scopeOfAccess['manager']) && in_array('indirect', $scopeOfAccess['manager'])) {
                    $scope['manager'][] = 'indirect';
                }
                $scope['manager'][] = 'direct';
                break;
            default:
                $scope = [];
                break;
        }
        return $scope;
    }

    /**
     * Function validate workflow of access
     * 
     * Usage:
     * $roleType = 'ADMIN'
     * $workflowAccess = [1,2,3]
     * 
     * Sample output:
     * [1,2,3]
     * 
     */
    public function filterWorkflows($roleType, $workflowAccess)
    {
        $workflowPermissions = [];

        if (in_array($roleType, [RoleType::ADMIN, RoleType::MANAGER])) {
            return $workflowAccess;
        }

        return $workflowPermissions;
    }

    /**
     * Following function creates a UserRole.

     * data: {id: 11, title: "ATL", type: "MANAGER", isDirectAccess: 0, isInDirectAccess: 1,…}
     * createdAt: "2021-08-23 03:58:42"
     * createdBy: 1
     * customCriteria: "{\"location\":[],\"department\":[],\"division\":[],\"employmentStatus\":[],\"jobTitle\":[]}"
     * editableFields: "{\"employee\":[\"initials\",\"firstName\",\"middleName\",\"hireDate\",\"recentHireDate\"],\"employment\":[],\"job\":[\"effectiveDate\",\"department\",\"employeeId\"],\"salary\":[],\"bankAccount\":[],\"dependent\":[],\"experience\":[],\"education\":[],\"competency\":[],\"emergencyContact\":[]}"
     * id: 11
     * isDirectAccess: 0
     * isEditable: 1
     * isInDirectAccess: 1
     * isVisibility: 1
     * permittedActions: "[\"USERROLECONTROLLER_CREATEUSER\",\"USERROLECONTROLLER_DELETEUSERROLE\"]"
     * readableFields: "{\"employee\":[\"initials\",\"firstName\",\"middleName\",\"hireDate\",\"recentHireDate\"],\"employment\":[],\"job\":[\"effectiveDate\",\"department\",\"id\",\"employeeId\"],\"salary\":[],\"bankAccount\":[],\"dependent\":[],\"experience\":[],\"education\":[],\"competency\":[],\"emergencyContact\":[]}"
     * title: "ATL"
     * type: "MANAGER"
     * updatedAt: "2021-08-23 03:58:42"
     * updatedBy: 1
     * workflowManagementActions: "[\"Attendence\",\"On-boarding-Event\",\"Performance-Appraisals\"]"
     * message: "User Role created Successfully."
     */

    public function createUserRole($userRole)
    {
        try {

            $userRole["permittedActions"] = $this->filterPermissions($userRole["type"], $userRole["permittedActions"]);

            $fieldLevelAccess = $this->filterFieldPermissions($userRole['fieldAccessLevels'], $userRole["type"]);

            $userRole['fieldPermissions'] = $fieldLevelAccess;

            $userRole['customCriteria'] = $this->filterScopeOfAccess($userRole["type"], $userRole["customCriteria"]);

            $userRole['workflowManagementActions'] = $this->filterWorkflows($userRole["type"], $userRole["workflowManagementActions"]);

            $userRoleMeta = $this->generateStorableUserRoleData($userRole);

            if (empty($userRoleMeta)) {
                return $this->error(400,  Lang::get('userRoleMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }

            $validationResponse = ModelValidator::validate($this->userRoleModel, $userRoleMeta);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('userRoleMessages.basic.ERR_CREATE'), $validationResponse);
            }

            // only global admin & system admin can create admin roles
            if (!($this->session->isGlobalAdmin() || $this->session->isSystemAdmin()) && $userRole['type'] == RoleType::ADMIN) {
                return $this->error(403, Lang::get('userRoleMessages.basic.ERR_NO_PERMISSION_CREATE_ADMIN_ROLE'), $validationResponse);
            }

            $newUserRole = $this->store->insert($this->userRoleModel, $userRoleMeta, true);
            return $this->success(201,  Lang::get('userRoleMessages.basic.SUCC_CREATE'), $newUserRole);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, $e->getMessage(), null);
        }
    }

    public function getAllUserRoles($permittedFields, $options)
    {
        try {
            if (!($this->session->isGlobalAdmin() || $this->session->isSystemAdmin())) {
                $customWhereClauses = [['type', '!=', RoleType::ADMIN], ['isEditable', '=', true]];
            } else {
                // hide system admin role
                $customWhereClauses = [['id', '!=', RoleType::SYSTEM_ADMIN_ID]];
            }

            $userRoles = $this->store->getAll(
                $this->userRoleModel,
                $permittedFields,
                $options,
                [],
                $customWhereClauses
            );

            return $this->success(200, "All User Roles retrieved Successfully!", $userRoles);
        } catch (Exception $e) {
            Log::error($e);
            return $this->error(500, $e->getMessage(), null);
        }
    }

    public function getAllAdminUserRoles($permittedFields, $options)
    {
        try {

            $userRoles = $this->store->getAll(
                $this->userRoleModel,
                $permittedFields,
                $options,
                [],
                [['type', '=', 'ADMIN'], ['id', '!=', 2]]
            );

            return $this->success(200, "All Admin User Roles retrieved Successfully!", $userRoles);
        } catch (Exception $e) {
            Log::error($e);
            return $this->error(500, $e->getMessage(), null);
        }
    }



    /**
     * Following function retrives a single user role for a provided id.
     *
     * @param $id user role id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $data => {title: "Genarel Manager", type: "MANAGER", isDirectAccess: 0, isInDirectAccess: 0,…}
     * ]
     */

    public function getUserRole($id)
    {
        try {

            // if (in_array($id, [RoleType::GLOBAL_ADMIN_ID, RoleType::SYSTEM_ADMIN_ID])) {
            //     return $this->error(403, Lang::get('userRoleMessages.basic.ERR_CAN_NOT_VISIBLE_SYSTEM_ROLES'), null);
            // }

            $userRole = $this->store->getById($this->userRoleModel, $id);
            $userRoleData = (array) $userRole;

            if (is_null($userRoleData)) {
                return $this->error(404, Lang::get('userRoleMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }

            $userRoleMeta = $this->generateReadableUserRoleData($userRoleData);
            if (is_null($userRoleMeta)) {
                return $this->error(404, Lang::get('userRoleMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }
            return $this->success(200, "Lang::get('userRoleMessages.basic.SUCC_SINGLE_RETRIVE')", $userRoleMeta);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, $e->getMessage(), null);
        }
    }

    /**
     *  updates a user role.
     *
     *  data: {title: "SSE", type: "ADMIN", isDirectAccess: 0, isInDirectAccess: 0,…}
     *     message: "User Role updated Successfully."
     */


    public function updateUserRole($id, $userRole)
    {
        try {
            $existingUserRole = $this->store->getById($this->userRoleModel, $id);
            if (is_null($existingUserRole)) {
                return $this->error(404, Lang::get('userRoleMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }

            // restrict to edit global admin role & system admin role
            if (in_array($id, [RoleType::GLOBAL_ADMIN_ID, RoleType::SYSTEM_ADMIN_ID])) {
                return $this->error(403, Lang::get('userRoleMessages.basic.ERR_CAN_NOT_EDIT_SYSTEM_ROLES'), null);
            }

            // only global admin ans system admin can edit admin roles
            if (!($this->session->isGlobalAdmin() || $this->session->isSystemAdmin()) && $userRole['type'] == RoleType::ADMIN) {
                return $this->error(403, Lang::get('userRoleMessages.basic.ERR_NO_PERMISSION_UPDATE_ADMIN_ROLE'), null);
            }

            $userRole["permittedActions"] = $this->filterPermissions($userRole["type"], $userRole["permittedActions"]);

            $fieldLevelAccess = $this->filterFieldPermissions($userRole['fieldAccessLevels'], $userRole["type"]);

            $userRole['fieldPermissions'] = $fieldLevelAccess;

            $userRole['customCriteria'] = $this->filterScopeOfAccess($userRole["type"], $userRole["customCriteria"]);

            $userRole['workflowManagementActions'] = $this->filterWorkflows($userRole["type"], $userRole["workflowManagementActions"]);

            $userRoleMeta = $this->generateStorableUserRoleData($userRole);
            if (empty($userRoleMeta)) {
                return $this->error(400, Lang::get('userRoleMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }
            // set role id
            $userRoleMeta['id'] = $id;
            $validationResponse = ModelValidator::validate($this->userRoleModel, $userRoleMeta, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('userRoleMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $result = $this->store->updateById($this->userRoleModel, $id, $userRoleMeta);

            if (!$result) {
                return $this->error(502, Lang::get('userRoleMessages.basic.ERR_UPDATE'), $id);
            }

            $this->redis->updateUserRole($id, $userRoleMeta);

            return $this->success(200, Lang::get('userRoleMessages.basic.SUCC_UPDATE'), $userRoleMeta);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, $e->getMessage(), null);
        }
    }

    /**
     *  delete a user role.
     *
    
     */
    /* TODO : enhans need to check binded users before delete role*/
    /* */
    public function deleteUserRole($id)
    {
        try {
            $existingUserRole = $this->store->getById($this->userRoleModel, $id);
            if (is_null($existingUserRole)) {
                return $this->error(404, Lang::get('userRoleMessages.basic.ERR_DELETE'), null);
            }

            // restrict to delete global admin role & system admin role
            if (in_array($id, [RoleType::GLOBAL_ADMIN_ID, RoleType::SYSTEM_ADMIN_ID])) {
                return $this->error(403, Lang::get('userRoleMessages.basic.ERR_CAN_NOT_DELETE_SYSTEM_ROLES'), null);
            }

            // only global admin can delete admin roles
            if (!$this->session->isGlobalAdmin() && $existingUserRole->type == RoleType::ADMIN) {
                return $this->error(403, Lang::get('userRoleMessages.basic.ERR_NO_PERMISSION_DELETE_ADMIN_ROLE'), null);
            }

            $user = $this->store->getFacade()::table('user')->where('employeeRoleId', $id)
                ->orWhere('managerRoleId', $id)
                ->orWhere('adminRoleId', $id)
                ->get();
            if ($user->count() > 0) {
                return $this->error(502, Lang::get('userRoleMessages.basic.ERR_NOTALLOWED'), null);
            }
            $result = $this->store->deleteById($this->userRoleModel, $id);

            if (!$result) {
                return $this->error(502, Lang::get('userRoleMessages.basic.ERR_DELETE'), $id);
            }

            return $this->success(200, Lang::get('userRoleMessages.basic.SUCC_DELETE'), $existingUserRole);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, $e->getMessage(), null);
        }
    }

    /**
     * Retrives all UserRoleMeta
     */
    public function getAllUserRoleJson()
    {
        try {
            $permissionConf = config('permission');

            if (!($this->session->isGlobalAdmin() || $this->session->isSystemAdmin())) {
                $permissionConf['roles'] = [
                    'EMPLOYEE',
                    'MANAGER'
                ];
            }

            $queryBuilder = $this->store->getFacade();

            // set location details
            $locations = $queryBuilder::table('location')->where('isDelete', 0)->pluck('name', 'id')->toArray();
            $permissionConf['rolePermissions']['ADMIN']['scopeAccess']['location'] = $locations;

            return $this->success(200, Lang::get('userRoleMessages.basic.SUCC_SINGLE_RETRIVE_MODEL_TEMPLATE'), $permissionConf);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('userRoleMessages.basic.ERR_SINGLE_RETRIVE_MODEL_TEMPLATE'), null);
        }
    }

    private function filterFieldPermissions($fieldLevelPermissions, $roleType)
    {
        $modelAttributes = [];

        foreach ($fieldLevelPermissions as $fieldAccess) {
            $key = $fieldAccess['key'];
            $modelData = explode(".", $key);
            $modelName = $modelData[0];
            $attribute = $modelData[1];

            if (!isset($modelAttributes[$modelName])) {
                $modelAttributes[$modelName] = ['viewOnly' => [], 'canEdit' => []];
            }

            $accessLevel = $fieldAccess['permission'];

            if ($accessLevel == "viewOnly") {
                array_push($modelAttributes[$modelName]['viewOnly'], $attribute);
            } else if ($accessLevel == "canEdit") {
                array_push($modelAttributes[$modelName]['canEdit'], $attribute);
            }
        }

        foreach ($modelAttributes as $modelName => &$accessLevelData) {
            $modelObj = $this->getModel($modelName, true);
            $modelFields = $modelObj->getAttributes();
            // check valid read permissions
            foreach ($accessLevelData['viewOnly'] as $field) {
                if (!in_array($field, $modelFields)) {
                    unset($accessLevelData['viewOnly'][$field]);
                }
            }
            // check valid write permissions
            foreach ($accessLevelData['canEdit'] as $field) {
                if (!in_array($field, $modelFields)) {
                    unset($accessLevelData['canEdit'][$field]);
                }
            }
            // set id field
            if (!empty($accessLevelData['canEdit'])) {
                array_push($accessLevelData['canEdit'], 'id', 'createdBy', 'updatedBy', 'createdAt', 'updatedAt');
            } else if (!empty($accessLevelData['viewOnly'])) {
                array_push($accessLevelData['viewOnly'], 'id', 'createdBy', 'updatedBy', 'createdAt', 'updatedAt');
            }
        }

        // handle employee effective data fields
        $employeeModelObj = $this->getModel('employee', true);
        $hasManyeModels = $employeeModelObj->getHasManyAttributesWithEffectiveDate();

        foreach ($hasManyeModels as $model) {
            $modelName = $model['modelName'];
            // set employee id 
            if (isset($modelAttributes[$modelName]['canEdit']) && !empty($modelAttributes[$modelName]['canEdit'])) {
                array_push($modelAttributes[$modelName]['canEdit'], 'employeeId');
            } else if (isset($modelAttributes[$modelName]['viewOnly']) && !empty($modelAttributes[$modelName]['viewOnly'])) {
                array_push($modelAttributes[$modelName]['viewOnly'], 'employeeId');
            }

            // only for effective date considerable fields
            if ($model['isEffectiveDateConsiderable']) {
                // set current attribute for employee model
                $attributeName = "current" . ucfirst($model['name']) . "Id";
                if (isset($modelAttributes[$modelName]['canEdit']) && !empty($modelAttributes[$modelName]['canEdit'])) {
                    if (isset($modelAttributes['employee']['canEdit'])) {
                        array_push($modelAttributes['employee']['canEdit'], $attributeName);
                    }
                }
                if (isset($modelAttributes[$modelName]['canEdit']) && !empty($modelAttributes[$modelName]['canEdit'])) {
                    if (isset($modelAttributes['employee']['canEdit'])) {
                        array_push($modelAttributes['employee']['canEdit'], $attributeName);
                    }
                } else if (isset($modelAttributes[$modelName]['viewOnly']) && !empty($modelAttributes[$modelName]['viewOnly'])) {
                    if (isset($modelAttributes['employee']['viewOnly'])) {
                        array_push($modelAttributes['employee']['viewOnly'], $attributeName);
                    }
                }
            }
        }

        // handle permissions for isActive  
        if (in_array($roleType, [RoleType::ADMIN, RoleType::MANAGER])) {
            array_push($modelAttributes['employee']['canEdit'], 'isActive');
        }

        // handle employeeJourney fields
        // $employeeJobModelObj = $this->getModel('employeeJob', true);
        $canEmployeeJourneySectionViewOnly = in_array('employeeJourney', $modelAttributes['employee']['viewOnly']);
        $canEmployeeJourneySectionEdit = in_array('employeeJourney', $modelAttributes['employee']['canEdit']);
        $modelAttributes['employeeJob']['viewOnly'] = [];
        $modelAttributes['employeeJob']['canEdit'] = [];

        if ($canEmployeeJourneySectionViewOnly) {
            $modelAttributes['employee']['viewOnly'] = array_merge($modelAttributes['employee']['viewOnly'], ['currentJobsId']);
            $modelAttributes['employeeJob']['viewOnly'] = array_merge(
                array_map(function ($field) {
                    return explode('.', $field)[1];
                }, array_keys($this->getRestrictedFields($employeeModelObj, 'jobs'))),
                ["id", "createdBy", "updatedBy", "createdAt", "updatedAt", "employeeId"]
            );
        }

        if ($canEmployeeJourneySectionEdit) {
            $modelAttributes['employee']['viewOnly'] = array_merge($modelAttributes['employee']['viewOnly'], ['currentJobsId']);
            $modelAttributes['employeeJob']['canEdit'] = array_merge(
                array_map(function ($field) {
                    return explode('.', $field)[1];
                }, array_keys($this->getRestrictedFields($employeeModelObj, 'jobs'))),
                ["id", "createdBy", "updatedBy", "createdAt", "updatedAt", "employeeId"]
            );
        }

        return $modelAttributes;
    }

    // private function getHasOneRelations($modelObj, $modelAttributes)
    // {
    //     $relations = $modelObj->getRelations(RelationshipType::HAS_ONE);

    //     $modelName = $modelObj->getName();

    //     dd($relations);

    //     foreach ($relations as $relation) {
    //         // get relational attribute permissions
    //         if (!isset($modelAttributes[$modelName])) {
    //             continue;
    //         }
    //         $modelPermissions = $modelAttributes[$modelName];
    //         $readPermissions = $modelPermissions['viewOnly'];
    //         $writePermissions = $modelPermissions['canEdit'];

    //         $foreignKey = sprintf("%s%s", $relation, 'Id');

    //         if (in_array($foreignKey, $readPermissions)) {

    //         }

    //         if (in_array($foreignKey, $writePermissions)) {

    //         }
    //     }

    // }

    /**
     * Get fields for access management
     */
    public function getAccessManagementFields()
    {
        try {
            $db = $this->store->getFacade();
            $employeeDefinition = $db::table('frontEndDefinition')->where(['modelName' => 'employee', 'alternative' => 'edit'])->first();

            if (is_null($employeeDefinition)) {
                return $this->error(404, Lang::get('userRoleMessages.basic.ERR_EMP_FRONT_DEFINITION_NOT_EXIST'), null);
            }
            // get employee model
            $employeeModelObj = $this->getModel('employee', true);
            $structure = json_decode($employeeDefinition->structure, true);

            $data = [];
            foreach ($structure as $tab) {
                $tabData = [
                    'key' => $tab['key'],
                    'label' => $tab['defaultLabel'],
                    'sections' => []
                ];
                // get section
                foreach ($tab['content'] as $section) {
                    $fields = [];
                    $readOnlyFields = [];
                    foreach ($section['content'] as $sectionField) {
                        $restrictedFields = $this->getRestrictedFields($employeeModelObj, $sectionField);
                        $fields = array_merge($fields, $restrictedFields);

                        $fieldDefinition = $employeeModelObj->getAttribute($sectionField);
                        if (isset($fieldDefinition['isComputedProperty']) && $fieldDefinition['isComputedProperty']) {
                            $readOnlyFields = array_merge($readOnlyFields, $restrictedFields);
                        }
                    }
                    if (!empty($fields)) {
                        $formattedFields = array_map(function ($key, $value) use ($readOnlyFields) {
                            if (isset($readOnlyFields[$key])) {
                                return ['key' => $key, 'value' => $value, 'readOnly' => true];
                            }

                            return ['key' => $key, 'value' => $value];
                        }, array_keys($fields), array_values($fields));
                        // $data[$tab['defaultLabel']][$section['defaultLabel']] = $fields;
                        array_push($tabData['sections'], [
                            'key' => $section['key'],
                            'label' => $section['defaultLabel'],
                            'fields' => $formattedFields
                        ]);
                    }
                }
                if (!empty($tabData['sections'])) {
                    array_push($data, $tabData);
                }
            }

            return $this->success(200, Lang::get('userRoleMessages.basic.SUCC_RETRIVE_ACCESS_MGT_FIELDS'), $data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('userRoleMessages.basic.ERR_RETRIVE_ACCESS_MGT_FIELDS'), null);
        }
    }

    /**
     * Get mandatory fields for access management
     */
    public function getAccessManagementMandatoryFields()
    {
        try {
            $db = $this->store->getFacade();
            $employeeDefinition = $db::table('frontEndDefinition')->where(['modelName' => 'employee', 'alternative' => 'edit'])->first();
            $modelWiseMandotaryFields = [];

            if (is_null($employeeDefinition)) {
                return $this->error(404, Lang::get('userRoleMessages.basic.ERR_EMP_FRONT_DEFINITION_NOT_EXIST'), null);
            }
            // get employee model
            $employeeModelObj = $this->getModel('employee', true);
            $structure = json_decode($employeeDefinition->structure, true);

            $data = [];
            foreach ($structure as $tab) {
                $tabData = [
                    'key' => $tab['key'],
                    'label' => $tab['defaultLabel'],
                    'sections' => []
                ];
                // get section
                foreach ($tab['content'] as $section) {
                    $fields = [];
                    foreach ($section['content'] as $sectionField) {
                        $restrictedFields = $this->getRestrictedFields($employeeModelObj, $sectionField);
                        $fields = array_merge($fields, $restrictedFields);
                    }

                    if (!empty($fields)) {

                        $mandotaryFields = [];
                        foreach ($fields as $fieldKey => $fieldValue) {
                            $keyArray = explode(".", $fieldKey);
                            $modelName = $keyArray[0];

                            if (!isset($modelWiseMandotaryFields[$modelName])) {
                                $modelObj =  $this->getModel($modelName, true);
                                $requiredFields = $modelObj->getRequiredFields();
                                $modelWiseMandotaryFields[$modelName] = $requiredFields;
                            }

                            if (in_array($keyArray[1], $modelWiseMandotaryFields[$modelName])) {
                                $mandotaryFields[] = $keyArray[1];
                            }
                        }

                        array_push($tabData['sections'], [
                            'key' => $section['key'],
                            'label' => $section['defaultLabel'],
                            'fields' => $mandotaryFields
                        ]);
                    }
                }
                if (!empty($tabData['sections'])) {
                    array_push($data, $tabData);
                }
            }

            return $this->success(200, Lang::get('userRoleMessages.basic.SUCC_RETRIVE_ACCESS_MGT_MAND_FIELDS'), $data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('userRoleMessages.basic.ERR_RETRIVE_ACCESS_MGT_MAND_FIELDS'), null);
        }
    }




    /**
     * Get model field list
     */
    private function getRestrictedFields($modelObject, $field)
    {
        // get model meta
        $fieldMeta = $modelObject->getAttribute($field);
        $modelName = $modelObject->getName();
        // if field meta not exist
        if (empty($fieldMeta)) {
            return [];
        }
        // get type of the attribute
        $type = isset($fieldMeta['type']) ? $fieldMeta['type'] : null;
        // ignore listView types
        if (is_null($type) || $type == 'listView' || $type == 'workSchedule') {
            return [];
        }
        // if attribute is modal
        if ($type == 'model') {
            // get relaton type
            $relationType = $modelObject->getRelationType($field);
            if (!isset($fieldMeta['modelName'])) {
                return [];
            }
            $relationalModelName = $fieldMeta['modelName'];
            // $enumValueKey = isset($fieldMeta['enumValueKey']) ? $fieldMeta['enumValueKey'] : 'id';
            // for has one relations
            if ($relationType == RelationshipType::HAS_ONE) {
                $key = sprintf("%s.%s%s", $modelName, $field, 'Id');
                return isset($fieldMeta['defaultLabel']) ? [$key => $fieldMeta['defaultLabel']] : [];
            }
            // ignore other relations other than has many
            if ($relationType != RelationshipType::HAS_MANY) {
                return [];
            }
            // get relational model meta
            $relationalModalObj = $this->getModel($relationalModelName, true);
            $attributes = $relationalModalObj->getAttributeKeys();

            $fields = [];
            foreach ($attributes as $attribute) {
                $restrictedFields = $this->getRestrictedFields($relationalModalObj, $attribute);
                $fields = array_merge($fields, $restrictedFields);
            }
            return $fields;
        }
        // ignore system fields
        if (isset($fieldMeta['isSystemValue']) && $fieldMeta['isSystemValue']) {
            return [];
        }
        $key = sprintf("%s.%s", $modelName, $fieldMeta['name']);
        $defaultLabel = isset($fieldMeta['defaultLabel']) ? [$key => $fieldMeta['defaultLabel']] : [];
        return $defaultLabel;
    }

    /**
     * Following function generates an array which is storable in the database from the frontend received json,
     * 
     * @param $reportData report data JSON from frontend
     * @return array
     * 
     * usage:
     * {customCriteria: {location: [], department: [], division: [], employmentStatus: [], jobTitle: []}
     *   editableFields: {employee: ["initials", "firstName", "middleName", "hireDate", "recentHireDate"], employment: [],…}
     *   isDirectAccess: 0
     *   isEditable: 1
     *   isInDirectAccess: 1
     *   isVisibility: 1
     *   permittedActions: ["USERROLECONTROLLER_CREATEUSER", "USERROLECONTROLLER_DELETEUSERROLE"]
     *   readableFields: {employee: ["initials", "firstName", "middleName", "hireDate", "recentHireDate"], employment: [],…}
     *   title: "ATL"
     *   type: "MANAGER"
     *   workflowManagementActions: ["Attendence", "On-boarding-Event", "Performance-Appraisals"]
     * 
     * Sample Output:
     *  {
     * customCriteria: "{\"location\":[],\"department\":[],\"division\":[],\"employmentStatus\":[],\"jobTitle\":[]}"
     * editableFields: "{\"employee\":[\"initials\",\"firstName\",\"middleName\",\"hireDate\",\"recentHireDate\"],\"employment\":[],\"job\":[\"effectiveDate\",\"department\",\"employeeId\"],\"salary\":[],\"bankAccount\":[],\"dependent\":[],\"experience\":[],\"education\":[],\"competency\":[],\"emergencyContact\":[]}"
     * permittedActions: "[\"USERROLECONTROLLER_CREATEUSER\",\"USERROLECONTROLLER_DELETEUSERROLE\"]"
     * readableFields: "{\"employee\":[\"initials\",\"firstName\",\"middleName\",\"hireDate\",\"recentHireDate\"],\"employment\":[],\"job\":[\"effectiveDate\",\"department\",\"id\",\"employeeId\"],\"salary\":[],\"bankAccount\":[],\"dependent\":[],\"experience\":[],\"education\":[],\"competency\":[],\"emergencyContact\":[]}"

     * workflowManagementActions: "[\"Attendence\",\"On-boarding-Event\",\"Performance-Appraisals\"]"
        
     */

    public function generateStorableUserRoleData($userRole)
    {
        try {
            $userRoleMeta["title"] = $userRole["title"];
            $userRoleMeta["type"] = $userRole["type"];
            $userRoleMeta["isDirectAccess"] = $userRole["isDirectAccess"];
            $userRoleMeta["isInDirectAccess"] = $userRole["isInDirectAccess"];
            $userRoleMeta["customCriteria"] = json_encode($userRole["customCriteria"]);
            $userRoleMeta["permittedActions"] = json_encode($userRole["permittedActions"]);
            $userRoleMeta["workflowManagementActions"] = json_encode($userRole["workflowManagementActions"]);
            $userRoleMeta["fieldPermissions"] = json_encode($userRole["fieldPermissions"]);
            $userRoleMeta["isEditable"] = $userRole["isEditable"];
            $userRoleMeta["isVisibility"] = $userRole["isVisibility"];

            return $userRoleMeta;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }

    /**
     * Following function generates a json which is readable to the frontend.
     * 
     * @param $reportData report data object from the database
     * @return array
     * 
     * usage:
     * {
     *  customCriteria: "{\"location\":[],\"department\":[],\"division\":[],\"employmentStatus\":[],\"jobTitle\":[]}"
     * editableFields: "{\"employee\":[\"initials\",\"firstName\",\"middleName\",\"hireDate\",\"recentHireDate\"],\"employment\":[],\"job\":[\"effectiveDate\",\"department\",\"employeeId\"],\"salary\":[],\"bankAccount\":[],\"dependent\":[],\"experience\":[],\"education\":[],\"competency\":[],\"emergencyContact\":[]}"
     * permittedActions: "[\"USERROLECONTROLLER_CREATEUSER\",\"USERROLECONTROLLER_DELETEUSERROLE\"]"
     * readableFields: "{\"employee\":[\"initials\",\"firstName\",\"middleName\",\"hireDate\",\"recentHireDate\"],\"employment\":[],\"job\":[\"effectiveDate\",\"department\",\"id\",\"employeeId\"],\"salary\":[],\"bankAccount\":[],\"dependent\":[],\"experience\":[],\"education\":[],\"competency\":[],\"emergencyContact\":[]}"

     * workflowManagementActions: "[\"Attendence\",\"On-boarding-Event\",\"Performance-Appraisals\"]"
   
     * 
     * 
     * Sample Output:
     * {customCriteria: {location: [], department: [], division: [], employmentStatus: [], jobTitle: []}
     *   editableFields: {employee: ["initials", "firstName", "middleName", "hireDate", "recentHireDate"], employment: [],…}
     *   isDirectAccess: 0
     *   isEditable: 1
     *   isInDirectAccess: 1
     *   isVisibility: 1
     *   permittedActions: ["USERROLECONTROLLER_CREATEUSER", "USERROLECONTROLLER_DELETEUSERROLE"]
     *   readableFields: {employee: ["initials", "firstName", "middleName", "hireDate", "recentHireDate"], employment: [],…}
     *   title: "ATL"
     *   type: "MANAGER"
     *   workflowManagementActions: ["Attendence", "On-boarding-Event", "Performance-Appraisals"]
     */
    public function generateReadableUserRoleData($userRoleData)
    {
        try {
            $userRoleMeta["title"] = $userRoleData["title"];
            $userRoleMeta["type"] = $userRoleData["type"];
            $userRoleMeta["isDirectAccess"] = $userRoleData["isDirectAccess"];
            $userRoleMeta["isInDirectAccess"] = $userRoleData["isInDirectAccess"];
            $userRoleMeta["customCriteria"] = json_decode($userRoleData["customCriteria"], true);
            $userRoleMeta["permittedActions"] = json_decode($userRoleData["permittedActions"], true);
            $userRoleMeta["workflowManagementActions"] = json_decode($userRoleData["workflowManagementActions"], true);
            $userRoleMeta["fieldPermissions"] = json_decode($userRoleData["fieldPermissions"], true);
            $userRoleMeta["isEditable"] = $userRoleData["isEditable"];
            $userRoleMeta["isVisibility"] = $userRoleData["isVisibility"];

            return $userRoleMeta;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }
}
