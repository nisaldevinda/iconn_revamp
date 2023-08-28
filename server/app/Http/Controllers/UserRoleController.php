<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserRoleService;


class UserRoleController extends Controller
{
    protected $userRoleService;
/**
     * UserRoleController constructor.
     *
     * @param UserRoleService $UserRoleController
     */
   
    public function __construct(UserRoleService $userRoleService)
    {
        $this->userRoleService  = $userRoleService;
    }

    /**
     * Retrives all users roles
     */

    public function getAllUserRoles(Request $request)
    {
        
        $permission = $this->grantPermission('access-levels-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $permittedFields = ["*"];
        $options = [
            "sorter" => $request->query('sorter', null),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', ['title', 'type'])
        ];

        $result = $this->userRoleService->getAllUserRoles($permittedFields, $options);
        return $this->jsonResponse($result);
    }
    

    /**
     * Retrives all admin users roles
     */

    public function getAllAdminUserRoles(Request $request)
    {
        $permission = $this->grantPermission('access-levels-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $permittedFields = ["*"];
        $options = [
            "sorter" => $request->query('sorter', null),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', ['title', 'type'])
        ];

        $result = $this->userRoleService->getAllAdminUserRoles($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single user role based on userRole_id.
    */
    public function getUserRole($id)
    {
        $permission = $this->grantPermission('access-levels-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->userRoleService->getUserRole($id);
        return $this->jsonResponse($result);
    }


    /*
        Creates a new User Role.
    */
    public function createUserRole(Request $request)
    {
        $permission = $this->grantPermission('access-levels-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
      
        $result = $this->userRoleService->createUserRole($data);
        return $this->jsonResponse($result);
    }

    /*
        A single user role is updated.
    */
    
    public function updateUserRole($id, Request $request)
    {
        $permission = $this->grantPermission('access-levels-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->userRoleService->updateUserRole($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        A single user role is delete.
    */
    public function deleteUserRole($id)
    {
        $permission = $this->grantPermission('access-levels-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->userRoleService->deleteUserRole($id);
        return $this->jsonResponse($result);
    }

     /**
     * Retrives all UserRoleMeta
     */
    public function getAllUserRoleMeta()
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->userRoleService->getAllUserRoleJson();
      
        return $this->jsonResponse($result);
    }

    public function getAccessManagementFields()
    {
        $result = $this->userRoleService->getAccessManagementFields();
        return $this->jsonResponse($result);
    }


    public function getAccessManagementMandatoryFields()
    {
        $result = $this->userRoleService->getAccessManagementMandatoryFields();
        return $this->jsonResponse($result);
    }


}
