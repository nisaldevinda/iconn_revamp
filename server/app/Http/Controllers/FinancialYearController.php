<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FinancialYearService;

/*
    Name: FinancialYearController
    Purpose: Performs request handling tasks related to the FinancialYear model.
    Description: API requests related to the FinancialYear model are directed to this controller.
    Module Creator: Tharindu Darshana
*/

class FinancialYearController extends Controller
{
    protected $winancialYearService;

    /**
     * FinancialYearController constructor.
     *
     * @param FinancialYearService $financialYearService
     */
    public function __construct(FinancialYearService $financialYearService)
    {
        $this->financialYearService  = $financialYearService;
    }

   
    /**
     * Retrives all Financial Years
     */
    public function getAllFinancialYears(Request $request)
    {
        // $permission = $this->grantPermission('ess');

        // if (!$permission->check()) {
        //     return $this->forbiddenJsonResponse();
        // }

        $permittedFields = ["*"];
        $options = [
            "sorter" => $request->query('sorter', null),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', ['financialDateRangeString']),
        ];
        $result = $this->financialYearService->getAllFinancialYears($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single leave type based on leave_type_id.
    */
    public function getFinancialYear($id)
    {
        
        $result = $this->financialYearService->getFinancialYear($id);
        return $this->jsonResponse($result);
    }

    /*
        Creates a new Financial Year
    */
    public function createFinancialYear(Request $request)
    {
        $permission = $this->grantPermission('financial-year-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->financialYearService->createFinancialYear($request->all());
        return $this->jsonResponse($result);
    }

    /*
        Update Financial Years
    */
    public function updateFinancialYear($id, Request $request)
    {
        $permission = $this->grantPermission('financial-year-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->financialYearService->updateFinancialYear($id, $request->all());
        return $this->jsonResponse($result);
    }
    
    /*
        Delete Financial Year
    */
    public function deleteFinancialYear($id)
    {
        $permission = $this->grantPermission('financial-year-read-write');
        
        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->financialYearService->deleteFinancialYear($id);
        return $this->jsonResponse($result);
    }
    
    
}