<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EmploymentStatusService;

/*
    Name: EmploymentStatusController
    Purpose: Performs request handling tasks related to the EmploymentStatus model.
    Description: API requests related to the EmploymentStatus model are directed to this controller.
    Module Creator: Yohan
*/

class EmploymentStatusController extends Controller
{
    protected $employmentStatus;

    /**
     * EmploymentStatusController constructor.
     *
     * @param EmploymentStatusService $employmentStatus
     */
    public function __construct(EmploymentStatusService $employmentStatus)
    {
        $this->employmentStatus  = $employmentStatus;
    }


    /*
        Creates a new EmploymentStatus.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->employmentStatus->createEmploymentStatus($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all EmploymentStatus
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
        $result = $this->employmentStatus->getAllEmploymentStatus($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single EmploymentStatus based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employmentStatus->getEmploymentStatus($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single EmploymentStatus based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->employmentStatus->getEmploymentStatusByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single EmploymentStatus is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employmentStatus->updateEmploymentStatus($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a EmploymentStatus
    */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->employmentStatus->softDeleteEmploymentStatus($id);
        return $this->jsonResponse($result);
    }
}
