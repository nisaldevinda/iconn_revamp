<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ConfirmationReasonService;

/*
    Name: ConfirmationReasonController
    Purpose: Performs request handling tasks related to the ConfirmationReason model.
    Description: API requests related to the ConfirmationReason model are directed to this controller.
    Module Creator: Hashan
*/

class ConfirmationReasonController extends Controller
{
    protected $confirmationReason;

    /**
     * ConfirmationReasonController constructor.
     *
     * @param ConfirmationReasonService $confirmationReason
     */
    public function __construct(ConfirmationReasonService $confirmationReason)
    {
        $this->confirmationReason  = $confirmationReason;
    }


    /*
        Creates a new ConfirmationReason.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->confirmationReason->createConfirmationReason($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all ConfirmationReasons
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
        $result = $this->confirmationReason->getAllConfirmationReasons($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single ConfirmationReason based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->confirmationReason->getConfirmationReason($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single ConfirmationReason based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->confirmationReason->getConfirmationReasonByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single ConfirmationReason is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->confirmationReason->updateConfirmationReason($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a ConfirmationReason
    */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->confirmationReason->softDeleteConfirmationReason($id);
        return $this->jsonResponse($result);
    }
}
