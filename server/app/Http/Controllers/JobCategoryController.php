<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\JobCategoryService;

/*
    Name: JobCategoryController
    Purpose: Performs request handling tasks related to the JobTitle model.
    Description: API requests related to the user model are directed to this controller.
    Module Creator: Chalaka
*/

class JobCategoryController extends Controller
{
    protected $jobCategoryService;

    /**
     * JobCategoryController constructor.
     *
     * @param JobCategoryService $jobCategoryService
     */
    public function __construct(JobCategoryService $jobCategoryService)
    {
        $this->jobCategoryService  = $jobCategoryService;
    }



    /*
        Creates a new Job Category.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->jobCategoryService->createJobCategory($data);
        return $this->jsonResponse($result);
    }


    /**
     * Retrives all job categories
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
        $result = $this->jobCategoryService->getAllJobCategory($permittedFields, $options);
        return $this->jsonResponse($result);
    }


    /*
        Retrives a single job category based on id.
    */
    public function show($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->jobCategoryService->getJobCategory($id);
        return $this->jsonResponse($result);
    }


    /*
        Retrives a single job category based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->jobCategoryService->getJobCategoryByKeyword($keyword);
        return $this->jsonResponse($result);
    }


    /*
        A single job category is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->jobCategoryService->updateJobCategory($id, $request->all());
        return $this->jsonResponse($result);
    }


    /*
        Delete a Job Category.
    */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->jobCategoryService->softDeleteJobCategory($id);
        return $this->jsonResponse($result);
    }
}
