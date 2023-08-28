<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BulkUploadService;

/*
    Name: BulkUploadController
    Purpose: Performs request handling tasks related to the Race model.
    Description: API requests related to the Race model are directed to this controller.
    Module Creator: Yohan
*/

class BulkUploadController extends Controller
{
    protected $bulkUploadService;

    /**
     *  BulkUploadController constructor.
     *
     * @param RaceService $race
     */
    public function __construct(BulkUploadService $bulkUploadService)
    {
        $this->bulkUploadService  = $bulkUploadService;
    }


    /*
        Downloads a new template.
    */
    public function downloadTemplate(Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $feildParams = [
            "modelName" => $request->query('modelName', null),
            "feildCount" => $request->query('feildCount', null),
        ];
        $result = $this->bulkUploadService->downloadTemplate($feildParams);
        return $this->jsonResponse($result);
    }

    /*
        Uploads a new template.
    */
    public function uploadTemplate(Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->bulkUploadService->uploadTemplate($request->all());
        return $this->jsonResponse($result);
    }

    /*
        Fetch all the bulk uploaded history data.
    */
    public function getBulkUploadedHistory()
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->bulkUploadService->getBulkUploadedHistory();
        return $this->jsonResponse($result);
    }

    /*
        Fetch a file object for a given file object id.
    */
    public function getFileObject($id)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->bulkUploadService->getFileObject($id);
        return $this->jsonResponse($result);
    }

     /*
        Downloads a new leave template.
    */
    public function downloadLeaveTemplate(Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->bulkUploadService->downloadLeaveTemplate();
        return $this->jsonResponse($result);
    }

    /*
        Uploads a new leave template.
    */
    public function uploadLeaveTemplate(Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->bulkUploadService->uploadLeaveTemplate($request->all());
        return $this->jsonResponse($result);
    }


    /*
        Save uploaded leave data .
    */
    public function saveValidatedUplodData(Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->bulkUploadService->saveValidatedUplodData($request->all());
        return $this->jsonResponse($result);
    }

    /*
        Downloads Salary Increment Template.
    */
    public function downloadSalaryIncrementTemplate(Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->bulkUploadService->downloadSalaryIncrementTemplate();
        return $this->jsonResponse($result);
    }

    /*
        Uploads a new template.
    */
    public function uploadSalaryIncrementSheet(Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->bulkUploadService->uploadSalaryIncrementSheet($request->all());
        return $this->jsonResponse($result);
    }

    /*
        Uploads a new template.
    */
    public function completeSalaryIncrementSheet(Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->bulkUploadService->completeSalaryIncrementSheet($request->all());
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all salary increment upload history.
     */
    public function getSalaryIncrementUploadHistory(Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

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

        $result = $this->bulkUploadService->getSalaryIncrementUploadHistory($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Rollback a salary increment upload
    */
    public function rollbackSalaryIncrementUpload($id, Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->bulkUploadService->rollbackSalaryIncrementUpload($id);
        return $this->jsonResponse($result);
    }

    /*
        Downloads Support Data for Employee Promotion Template.
    */
    public function getEmployeePromotionSupportData(Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->bulkUploadService->getEmployeePromotionSupportData();
        return $this->jsonResponse($result);
    }

    /*
        Downloads Employee Promotion Template.
    */
    public function downloadEmployeePromotionTemplate(Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->bulkUploadService->downloadEmployeePromotionTemplate();
        return $this->jsonResponse($result);
    }

    /*
        Uploads a new template.
    */
    public function uploadEmployeePromotionSheet(Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->bulkUploadService->uploadEmployeePromotionSheet($request->all());
        return $this->jsonResponse($result);
    }

    /*
        Uploads a new template.
    */
    public function completeEmployeePromotionSheet(Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->bulkUploadService->completeEmployeePromotionSheet($request->all());
        return $this->jsonResponse($result);
    }

    /**
     * Retrieves all Employee Promotion upload history.
     */
    public function getEmployeePromotionUploadHistory(Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

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

        $result = $this->bulkUploadService->getEmployeePromotionUploadHistory($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Rollback a Employee Promotion upload
    */
    public function rollbackEmployeePromotionUpload($id, Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->bulkUploadService->rollbackEmployeePromotionUpload($id);
        return $this->jsonResponse($result);
    }

    /*
        Downloads Support Data for Employee Transfer Template.
    */
    public function getEmployeeTransferSupportData(Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->bulkUploadService->getEmployeeTransferSupportData();
        return $this->jsonResponse($result);
    }

    /*
        Downloads Employee Transfer Template.
    */
    public function downloadEmployeeTransferTemplate(Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->bulkUploadService->downloadEmployeeTransferTemplate();
        return $this->jsonResponse($result);
    }

    /*
        Uploads a new template.
    */
    public function uploadEmployeeTransferSheet(Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->bulkUploadService->uploadEmployeeTransferSheet($request->all());
        return $this->jsonResponse($result);
    }

    /*
        Uploads a new template.
    */
    public function completeEmployeeTransferSheet(Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->bulkUploadService->completeEmployeeTransferSheet($request->all());
        return $this->jsonResponse($result);
    }

    /**
     * Retrieves all Employee Transfer upload history.
     */
    public function getEmployeeTransferUploadHistory(Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

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

        $result = $this->bulkUploadService->getEmployeeTransferUploadHistory($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Rollback a Employee Transfer upload
    */
    public function rollbackEmployeeTransferUpload($id, Request $request)
    {
        $permission = $this->grantPermission('bulk-upload-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->bulkUploadService->rollbackEmployeeTransferUpload($id);
        return $this->jsonResponse($result);
    }

}
