<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ClaimTypeService;

/*
    Name: ClaimTypeController
    Purpose: Performs request handling tasks related to the ClaimType model.
    Description: API requests related to the ClaimType model are directed to this controller.
    Module Creator: Tharindu Darshana
*/

class ClaimTypeController extends Controller
{
    protected $claimTypeService;

    /**
     * ClaimTypeController constructor.
     *
     * @param ClaimTypeService $claimTypeService
     */
    public function __construct(ClaimTypeService $claimTypeService)
    {
        $this->claimTypeService  = $claimTypeService;
    }

   
    /**
     * Retrives all Financial Years
     */
    public function getAllClaimTypes(Request $request)
    {
        $permission = $this->grantPermission('expense-management-read-write');

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
            "searchFields" => $request->query('search_fields', ['typeName']),
        ];
        $result = $this->claimTypeService->getAllClaimTypes($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single leave type based on leave_type_id.
    */
    public function getClaimType($id)
    {
        
        $result = $this->claimTypeService->getClaimType($id);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all Financial Years
     */
    public function getEmployeeClaimAllocationList(Request $request)
    {
        // $permission = $this->grantPermission('workflow-management-read-write');

        // if (!$permission->check()) {
        //     return $this->forbiddenJsonResponse();
        // }

        $result = $this->claimTypeService->getEmployeeClaimAllocationList($request);
        return $this->jsonResponse($result);
    }


    /**
     * Retrives all Financial Years
     */
    public function getAllocationEnableClaimTypes(Request $request)
    {
        $permission = $this->grantPermission('workflow-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        
        $result = $this->claimTypeService->getAllocationEnableClaimTypes();
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all Financial Years
     */
    public function getEmployeeEligibleClaimTypes(Request $request)
    {
        $permission = $this->grantPermission('ess');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        
        $result = $this->claimTypeService->getEmployeeEligibleClaimTypes();
        return $this->jsonResponse($result);
    }


    /**
     * Retrives all Financial Years
     */
    public function getEmployeeClaimAllocationData(Request $request)
    {
        $permission = $this->grantPermission('ess');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        
        $result = $this->claimTypeService->getEmployeeClaimAllocationData($request);
        return $this->jsonResponse($result);
    }

    /** 
     * Retrives all Financial Years
     */
    public function getClaimTypesByEntityId(Request $request)
    {
        // $permission = $this->grantPermission('workflow-management-read-write');

        // if (!$permission->check()) {
        //     return $this->forbiddenJsonResponse();
        // }

        $orgEntityId = $request->query('orgEntityId', null);

        $result = $this->claimTypeService->getClaimTypesByEntityId($orgEntityId);
        return $this->jsonResponse($result);
    }

    /*
        Creates a new Financial Year
    */
    public function createClaimType(Request $request)
    {
        $permission = $this->grantPermission('expense-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->claimTypeService->createClaimType($request->all());
        return $this->jsonResponse($result);
    }


    /*
        Creates a new Financial Year
    */
    public function addEmployeeClaimAllocation(Request $request)
    {
        $permission = $this->grantPermission('expense-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->claimTypeService->addEmployeeClaimAllocation($request->all());
        return $this->jsonResponse($result);
    }


    /*
        Creates a new Financial Year
    */
    public function addBulkEmployeeClaimAllocation(Request $request)
    {
        $permission = $this->grantPermission('expense-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->claimTypeService->addBulkEmployeeClaimAllocation($request->all());
        return $this->jsonResponse($result);
    }

    /*
        Update Financial Years
    */
    public function updateClaimType($id, Request $request)
    {
        $permission = $this->grantPermission('expense-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->claimTypeService->updateClaimType($id, $request->all());
        return $this->jsonResponse($result);
    }


    /*
        Update Financial Years
    */
    public function updateEmployeeClaimAllocations(Request $request)
    {
        $permission = $this->grantPermission('expense-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->claimTypeService->updateEmployeeClaimAllocations($request->all());
        return $this->jsonResponse($result);
    }
    
    /*
        Delete Financial Year
    */
    public function deleteClaimType($id)
    {
        $permission = $this->grantPermission('expense-management-read-write');
        
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->claimTypeService->deleteClaimType($id);
        return $this->jsonResponse($result);
    }

    
    /*
        Delete Financial Year
    */
    public function deleteEmployeeClaimAllocation($id)
    {
        $permission = $this->grantPermission('expense-management-read-write');
        
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->claimTypeService->deleteEmployeeClaimAllocation($id);
        return $this->jsonResponse($result);
    }
    
    
}