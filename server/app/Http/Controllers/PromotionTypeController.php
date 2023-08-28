<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PromotionTypeService;

/*
    Name: PromotionTypeController
    Purpose: Performs request handling tasks related to the PromotionType model.
    Description: API requests related to the PromotionType model are directed to this controller.
    Module Creator: Hashan
*/

class PromotionTypeController extends Controller
{
    protected $promotionType;

    /**
     * PromotionTypeController constructor.
     *
     * @param PromotionTypeService $promotionType
     */
    public function __construct(PromotionTypeService $promotionType)
    {
        $this->promotionType  = $promotionType;
    }


    /*
        Creates a new PromotionType.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->promotionType->createPromotionType($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all PromotionTypes
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
        $result = $this->promotionType->getAllPromotionTypes($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single PromotionType based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->promotionType->getPromotionType($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single PromotionType based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->promotionType->getPromotionTypeByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single PromotionType is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->promotionType->updatePromotionType($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a PromotionType
    */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->promotionType->softDeletePromotionType($id);
        return $this->jsonResponse($result);
    }
}
