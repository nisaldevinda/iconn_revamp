<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ClaimCategoryService;

/*
    Name: ClaimCategoryController
    Purpose: Performs request handling tasks related to the ClaimCategory model.
    Description: API requests related to the ClaimCategory model are directed to this controller.
    Module Creator: Tharindu Darshana
*/

class ClaimCategoryController extends Controller
{
    protected $claimCategoryService;

    /**
     * ClaimCategoryController constructor.
     *
     * @param ClaimCategoryService $claimCategoryService
     */
    public function __construct(ClaimCategoryService $claimCategoryService)
    {
        $this->claimCategoryService  = $claimCategoryService;
    }

   
    /**
     * Retrives all Claim Categories
     */
    public function getAllClaimCategories(Request $request)
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
        $result = $this->claimCategoryService->getAllClaimCategories($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Creates a new Claim Category
    */
    public function createClaimCategory(Request $request)
    {
        $permission = $this->grantPermission('expense-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->claimCategoryService->createClaimCategory($request->all());
        return $this->jsonResponse($result);
    }

    /*
        Update Claim Category
    */
    public function updateClaimCategory($id, Request $request)
    {
        $permission = $this->grantPermission('expense-management-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->claimCategoryService->updateClaimCategory($id, $request->all());
        return $this->jsonResponse($result);
    }
    
    /*
        Delete Claim Category
    */
    public function deleteClaimCategory($id)
    {
        $permission = $this->grantPermission('expense-management-read-write');
        
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->claimCategoryService->deleteClaimCategory($id);
        return $this->jsonResponse($result);
    }
    
    
}