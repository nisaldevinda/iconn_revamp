<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PayGradesService;
use Log;

/*
    Name: PayGradesController
    Purpose: Performs request handling tasks related to the PayGrades model.
    Description: API requests related to the PayGrades model are directed to this controller.
    Module Creator: Chalaka
*/

class PayGradesController extends Controller
{
    protected $payGradesService;

    /**
     * PayGradesController constructor.
     *
     * @param PayGradesService $payGradesService
     */
    public function __construct(PayGradesService $payGradesService)
    {
        $this->payGradesService  = $payGradesService;
    }


    /*
        Creates a new PayGrades.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->payGradesService->createPayGrades($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all PayGrades
     */
    public function index(Request $request)
    {
        $permission = $this->grantPermission('master-data-read');

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
        $result = $this->payGradesService->getAllPayGrades($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single PayGrades based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->payGradesService->getPayGrades($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single PayGrades based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->payGradesService->getPayGradesByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single PayGrades is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->payGradesService->updatePayGrades($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a PayGrades
    */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->payGradesService->softDeletePayGrades($id);
        return $this->jsonResponse($result);
    }
}
