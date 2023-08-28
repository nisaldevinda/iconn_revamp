<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TerminationReasonService;

/*
    Name: TerminationReasonController
    Purpose: Performs request handling tasks related to the TerminationReason model.
    Description: API requests related to the TerminationReason model are directed to this controller.
    Module Creator: Chalaka
*/

class TerminationReasonController extends Controller
{
    protected $terminationReason;

    /**
     * TerminationReasonController constructor.
     *
     * @param TerminationReasonService $terminationReason
     */
    public function __construct(TerminationReasonService $terminationReason)
    {
        $this->terminationReason  = $terminationReason;
    }


    /*
        Creates a new TerminationReason.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->terminationReason->createTerminationReason($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all TerminationReasons
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
        $result = $this->terminationReason->getAllTerminationReasons($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single TerminationReason based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->terminationReason->getTerminationReason($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single TerminationReason based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->terminationReason->getTerminationReasonByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single TerminationReason is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->terminationReason->updateTerminationReason($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a TerminationReason
    */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->terminationReason->softDeleteTerminationReason($id);
        return $this->jsonResponse($result);
    }
}
