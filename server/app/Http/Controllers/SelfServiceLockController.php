<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SelfServiceLockService;

/*
    Name: SelfServiceLockController
*/

class SelfServiceLockController extends Controller
{
    protected $selfServiceLockService;

    /**
     * SelfServiceLockController constructor.
     *
     * @param SelfServiceLockService $selfServiceLockService
     */
    public function __construct(SelfServiceLockService $selfServiceLockService)
    {
        $this->selfServiceLockService  = $selfServiceLockService;
    }


    /*
        Creates a new Self Service Lock Date Period.
    */
    public function createDatePeriods(Request $request)
    {
        $permission = $this->grantPermission('self-service-lock');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->selfServiceLockService->createDatePeriods($data);
        return $this->jsonResponse($result);
    }
    
    /**
     * Retrives all Self Service Lock Date Periods
     */
    public function getDatePeriods(Request $request)
    {
        $permission = $this->grantPermission('self-service-lock');
        
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
            "searchFields" => $request->query('search_fields', ['configuredMonth']),
        ];
        $result = $this->selfServiceLockService->getDatePeriods($permittedFields, $options);
        return $this->jsonResponse($result);
    }
    
    /**
     * Retrives all Self Service Lock Date Period
     */
    public function getAllDatePeriods(Request $request)
    {
        $permission = $this->grantPermission('self-service-lock');
        
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
            "searchFields" => $request->query('search_fields', ['configuredMonth']),
        ];
        $result = $this->selfServiceLockService->getAllDatePeriods($permittedFields, $options);
        return $this->jsonResponse($result);
    }
    
    /*
     *   Retrives a single Self Service Lock Date Period based on id.
    */
    public function getDatePeriod($id)
    {
        $permission = $this->grantPermission('self-service-lock');
        
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->selfServiceLockService->getDatePeriod($id);
        return $this->jsonResponse($result);
    }
    
    
    /*
     * update Self Service Lock Date Period
    */
    public function updateDatePeriods($id, Request $request)
    {
        $permission = $this->grantPermission('self-service-lock');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->selfServiceLockService->updateDatePeriods($id, $request->all());
        return $this->jsonResponse($result);
    }
    
    /*
     * Delete a  Self Service Lock Date Period
    */
    public function deleteDatePeriods($id, Request $request)
    {
        $permission = $this->grantPermission('self-service-lock');
        
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->selfServiceLockService->deleteDatePeriods($id);
        return $this->jsonResponse($result);
    }


    /*
        Creates a new Self Service Lock Configuration.
    */
    public function createSelfServiceLockConfig(Request $request)
    {
        $permission = $this->grantPermission('self-service-lock');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->selfServiceLockService->createSelfServiceLockConfig($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all Self Service Lock Configurations
     */
    public function getAllSelfServiceLockConfigs(Request $request)
    {
        $permission = $this->grantPermission('self-service-lock');
        
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
            "searchFields" => $request->query('search_fields', ['configuredMonth']),
            "filterBy"=>$request->query('filterBy',null),
        ];
        $result = $this->selfServiceLockService->getAllSelfServiceLockConfigs($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
    Retrives a Self Service Lock Configuration based on id.
    */
    public function getSelfServiceLockConfig($id)
    {
        $permission = $this->grantPermission('self-service-lock');
        
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->selfServiceLockService->getSelfServiceLockConfig($id);
        return $this->jsonResponse($result);
    }

    /*
     * Update Self Service Lock Configuration
    */
    public function updateSelfServiceLockConfig($id, Request $request)
    {
        $permission = $this->grantPermission('self-service-lock');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->selfServiceLockService->updateSelfServiceLockConfig($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
     * Delete Self Service Lock Configurations
    */
    public function deleteSelfServiceLockConfig($id, Request $request)
    {
        $permission = $this->grantPermission('self-service-lock');
        
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->selfServiceLockService->deleteSelfServiceLockConfig($id);
        return $this->jsonResponse($result);
    }
}
