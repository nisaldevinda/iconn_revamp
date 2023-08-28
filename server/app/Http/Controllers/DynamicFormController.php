<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DynamicFormService;

/*
    Name: DynamicFormController
    Purpose: Performs request handling tasks related to the Dynamic Form model.
    Description: API requests related to the Dynamic Form model are directed to this controller.
    Module Creator: Chalaka
*/

class DynamicFormController extends Controller
{
    protected $dynamicFormService;

    /**
     * DynamicFormController constructor.
     *
     * @param DynamicFormService $dynamicFormService
     */
    public function __construct(DynamicFormService $dynamicFormService)
    {
        $this->dynamicFormService  = $dynamicFormService;
    }

    /*
        Creates a new Dynamic Form.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->dynamicFormService->createDynamicForm($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all Dynamic Forms
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
        $result = $this->dynamicFormService->getAllDynamicForms($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single Dynamic Form based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->dynamicFormService->getDynamicForm($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single Dynamic Form based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->dynamicFormService->getDynamicFormByKeyword($keyword);
        return $this->jsonResponse($result);
    }


    /*
        A single Dynamic Form is updated.
    */
    public function update($modelName, $alternative = null, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->dynamicFormService->updateDynamicForm($modelName, $alternative, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a Dynamic Form
    */
    public function delete($modelName, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->dynamicFormService->softDeleteDynamicForm($modelName);
        return $this->jsonResponse($result);
    }

    /*
        update Alternative Layout.
    */
    public function updateAlternativeLayout($modelName, $alternative, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->dynamicFormService->updateAlternativeLayout($modelName, $alternative, $request->all());
        return $this->jsonResponse($result);
    }
}
