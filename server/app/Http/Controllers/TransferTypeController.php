<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TransferTypeService;

/*
    Name: TransferTypeController
    Purpose: Performs request handling tasks related to the TransferType model.
    Description: API requests related to the TransferType model are directed to this controller.
    Module Creator: Hashan
*/

class TransferTypeController extends Controller
{
    protected $transferType;

    /**
     * TransferTypeController constructor.
     *
     * @param TransferTypeService $transferType
     */
    public function __construct(TransferTypeService $transferType)
    {
        $this->transferType  = $transferType;
    }


    /*
        Creates a new TransferType.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->transferType->createTransferType($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all TransferTypes
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
        $result = $this->transferType->getAllTransferTypes($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single TransferType based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->transferType->getTransferType($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single TransferType based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->transferType->getTransferTypeByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single TransferType is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->transferType->updateTransferType($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a TransferType
    */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->transferType->softDeleteTransferType($id);
        return $this->jsonResponse($result);
    }
}
