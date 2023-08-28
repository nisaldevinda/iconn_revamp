<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ClaimPackageService;

/*
    Name: ClaimPackageController
    Purpose: Performs request handling tasks related to the ClaimType model.
    Description: API requests related to the ClaimType model are directed to this controller.
    Module Creator: Tharindu Darshana
*/

class ClaimPackageController extends Controller
{
    protected $claimPackageService;

    /**
     * ClaimPackageController constructor.
     *
     * @param ClaimPackageService $claimPackageService
     */
    public function __construct(ClaimPackageService $claimPackageService)
    {
        $this->claimPackageService  = $claimPackageService;
    }

   
    /**
     * Retrives all Claim Packages
     */
    public function getAllClaimPackages(Request $request)
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
            "searchFields" => $request->query('search_fields', ['name']),
        ];
        $result = $this->claimPackageService->getAllClaimPackages($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Creates a new Claim Packages
    */
    public function createClaimPackage(Request $request)
    {
        $permission = $this->grantPermission('expense-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->claimPackageService->createClaimPackage($request->all());
        return $this->jsonResponse($result);
    }

    /*
        Update Claim Packages
    */
    public function updateClaimPackage($id, Request $request)
    {
        $permission = $this->grantPermission('expense-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->claimPackageService->updateClaimPackage($id, $request->all());
        return $this->jsonResponse($result);
    }
    
    /*
        Delete Claim Packages
    */
    public function deleteClaimPackage($id)
    {
        $permission = $this->grantPermission('expense-management-read-write');
        
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->claimPackageService->deleteClaimPackage($id);
        return $this->jsonResponse($result);
    }
    
    
}