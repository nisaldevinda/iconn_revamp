<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NoticeCategoryService;

/*
    Name: NoticeCategoryController
    Purpose: Performs request handling tasks related to the NoticeCategory model.
    Description: API requests related to the NoticeCategory model are directed to this controller.
    Module Creator: Hashan
*/

class NoticeCategoryController extends Controller
{
    protected $noticeCategoryService;

    /**
     * NoticeCategoryController constructor.
     *
     * @param NoticeCategoryService $noticeCategoryService
     */
    public function __construct(NoticeCategoryService $noticeCategoryService)
    {
        $this->noticeCategoryService  = $noticeCategoryService;
    }


    /*
        Creates a new NoticeCategory.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->noticeCategoryService->createNoticeCategory($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all NoticeCategorys
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
        $result = $this->noticeCategoryService->getAllNoticeCategorys($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single NoticeCategory based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->noticeCategoryService->getNoticeCategory($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single NoticeCategory based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->noticeCategoryService->getNoticeCategoryByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single NoticeCategory is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->noticeCategoryService->updateNoticeCategory($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a NoticeCategory
    */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->noticeCategoryService->softDeleteNoticeCategory($id);
        return $this->jsonResponse($result);
    }
}
