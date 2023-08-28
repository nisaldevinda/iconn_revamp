<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReportDataService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/*
    Name: ReportDataController
    Purpose: Performs request handling tasks related to the ReportData model.
    Description: API requests related to the ReportData model are directed to this controller.
    Module Creator: Chalaka
*/

class ReportDataController extends Controller
{
    protected $reportDataService;

    /**
     * ReportDataController constructor.
     *
     * @param ReportDataService $reportDataService
     */
    public function __construct(ReportDataService $reportDataService)
    {
        $this->reportDataService  = $reportDataService;
    }
    
    
    /*
        Creates a new Report.
    */
    public function store(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $result = $this->reportDataService->storeReportData($data);
        return $this->jsonResponse($result);
    }


    /**
     * Retrives all ReportData
     */
    public function getAdminReportList(Request $request)
    {
        $permission = $this->grantPermission('admin-reports');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $permittedFields = ["*"];
        $options = [
           
            "sorter" =>$request->query('sorter', ' {"id":"descend"} '),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => ' {"isAdminReport":"eq:1"}',
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', ['reportName']),
        ];
        $result = $this->reportDataService->getAllReportData($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all ReportDatas
     */
    public function getManagerReportList(Request $request)
    {
        $permission = $this->grantPermission('manager-reports');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $permittedFields = ["*"];
        $options = [
           
            "sorter" =>$request->query('sorter', ' {"id":"descend"} '),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => ' {"isManagerReport":"eq:1"}',
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', ['reportName']),
        ];
        $result = $this->reportDataService->getAllReportData($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all ReportDatas
     */
    public function getEmployeeReportList(Request $request)
    {
        $permission = $this->grantPermission('employee-reports');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $permittedFields = ["*"];
        $options = [
           
            "sorter" =>$request->query('sorter', ' {"id":"descend"} '),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => ' {"isEmployeeReport":"eq:1"}',
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', ['reportName']),
        ];
        $result = $this->reportDataService->getAllReportData($permittedFields, $options);
        return $this->jsonResponse($result);
    }
    /**
     * Retrives all ReportData
     */
    public function showById($id, Request $request)
    {
        $result = $this->reportDataService->getReportDataById($id);
        return $this->jsonResponse($result);
    }


    /**
     * Retrives an admin report
     */
    public function getAdminReport($id)
    {
        $permission = $this->grantPermission('admin-reports');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        if($id=="head-count"){
            $result = $this->reportDataService->generateHeadCountReport();
            return $this->jsonResponse($result);
        }
        $result = $this->reportDataService->generateReport($id,"report");
        return $this->jsonResponse($result);
    }

    /**
     * Retrives an manager report
     */
    public function getManagerReport($id)
    {
        $permission = $this->grantPermission('manager-reports');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        if($id=="head-count"){
            $result = $this->reportDataService->generateHeadCountReport();
            return $this->jsonResponse($result);
        }
        $result = $this->reportDataService->generateReport($id,"report");
        return $this->jsonResponse($result);
    }

        /**
     * Retrives an employee report
     */
    public function getEmployeeReport($id)
    {
        $permission = $this->grantPermission('employee-reports');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        if($id=="head-count"){
            $result = $this->reportDataService->generateHeadCountReport();
            return $this->jsonResponse($result);
        }
        $result = $this->reportDataService->generateReport($id,"report");
        return $this->jsonResponse($result);
    }

    /**
     * Retrives a report
     */
    public function queryReportWithDynamicFilters($id, Request $request)
    {
        $data = $request->query();
        $result = $this->reportDataService->queryReportWithDynamicFilters($id, $data);
        return $this->jsonResponse($result);
    }


    /**
     * Retrives report names and IDs to generate the dropdown
     */
    public function getReportNamesWithId(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $result = $this->reportDataService->getReportNamesWithId();
        return $this->jsonResponse($result);
    }

    /**
     * Retrieves filter definitions for report 
     */
    public function getFilterDefinitions()
    {
        $result = $this->reportDataService->getFilterDefinitions();
        return $this->jsonResponse($result);
    }

     /*
        Delete a report
    */
    public function delete($id)
    {
      
        $result = $this->reportDataService->deleteReport($id);
        return $this->jsonResponse($result);
    }

        /*
        A single report data is updated.
    */
    public function update($id, Request $request)
    {

        $result = $this->reportDataService->update($id, $request->all());
        return $this->jsonResponse($result);
    }


    /**
     * Retrives a report
     */
    public function getAdminChart($id, Request $request)
    {
        $permission = $this->grantPermission('admin-reports');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $data = json_decode($request->getContent(), true);
        $result = $this->reportDataService->generateReport($id,"chart");
        return $this->jsonResponse($result);
    }


    /**
     * Retrives a report
     */
    public function getManagerChart($id, Request $request)
    {
        $permission = $this->grantPermission('manager-reports');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $data = json_decode($request->getContent(), true);
        $result = $this->reportDataService->generateReport($id,"chart");
        return $this->jsonResponse($result);
    }


    /**
     * Retrives a report
     */
    public function getEmployeeChart($id, Request $request)
    {
        $permission = $this->grantPermission('employee-reports');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $data = json_decode($request->getContent(), true);
        $result = $this->reportDataService->generateReport($id,"chart");
        return $this->jsonResponse($result);
    }

    /*
        get Admin view of leave request data according to page count, page size, search, and sort.
    */
    public function downloadAdminReportByFormat($id, Request $request)
    {

        $permission = $this->grantPermission('admin-reports');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $options = [
            'reportId' => $id,
            'reportFormat' => (!empty($request['format'])) ? $request['format'] : 'xls'
        ];
        
        $result = $this->reportDataService->downloadReportByFormat($options);
        return $this->jsonResponse($result);
    }

     /*
        get Admin view of leave request data according to page count, page size, search, and sort.
    */
    public function downloadManagerReportByFormat($id, Request $request)
    {
        $permission = $this->grantPermission('manager-reports');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $options = [
            'reportId' => $id,
            'reportFormat' => (!empty($request['format'])) ? $request['format'] : 'xls'
        ];

        $result = $this->reportDataService->downloadReportByFormat($options);
        return $this->jsonResponse($result);
    }

     /*
        get Admin view of leave request data according to page count, page size, search, and sort.
    */
    public function downloadEmployeeReportByFormat($id, Request $request)
    {
        $permission = $this->grantPermission('employee-reports');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $options = [
            'reportId' => $id,
            'reportFormat' => (!empty($request['format'])) ? $request['format'] : 'xls'
        ];

        $result = $this->reportDataService->downloadReportByFormat($options);
        return $this->jsonResponse($result);
    }
    
}
