<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NoticeService;


class NoticeController extends Controller
{
    protected $noticeService;
    /**
     * NoticeController constructor.
     *
     * @param NoticeService $NoticeController
     */

    public function __construct(NoticeService $noticeService)
    {
        $this->noticeService  = $noticeService;
    }

    /**
     * Retrieves all notices
     */
    public function getAllNotices(Request $request)
    {
        $companyNoticePermission = $this->grantPermission('company-notice-read-write');
        $teamNoticePermission = $this->grantPermission('team-notice-read-write');

        if (!$companyNoticePermission->check() && !$teamNoticePermission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $permittedFields = ["*"];
        $options = [
            "sorter" => $request->query('sorter', null),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', ['topic', 'status']),
            "filterBy"=>$request->query('filterBy',null),
        ];

        $result = $this->noticeService->getAllNotices($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrieves a single notice based on notice id.
    */
    public function getNotice($id)
    {
        $companyNoticePermission = $this->grantPermission('company-notice-read-write');
        $teamNoticePermission = $this->grantPermission('team-notice-read-write');

        if (!$companyNoticePermission->check() && !$teamNoticePermission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->noticeService->getNotice($id);
        return $this->jsonResponse($result);
    }

    /*
        Creates a new Notice.
    */
    public function createNotice(Request $request)
    {
        $companyNoticePermission = $this->grantPermission('company-notice-read-write');
        $teamNoticePermission = $this->grantPermission('team-notice-read-write');

        if (!$companyNoticePermission->check() && !$teamNoticePermission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->noticeService->createNotice($data);
        return $this->jsonResponse($result);
    }

    /*
        A single notice update.
    */
    public function updateNotice($id, Request $request)
    {
        $companyNoticePermission = $this->grantPermission('company-notice-read-write');
        $teamNoticePermission = $this->grantPermission('team-notice-read-write');

        if (!$companyNoticePermission->check() && !$teamNoticePermission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->noticeService->updateNotice($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        A single notice delete.
    */
    public function deleteNotice($id)
    {
        $companyNoticePermission = $this->grantPermission('company-notice-read-write');
        $teamNoticePermission = $this->grantPermission('team-notice-read-write');

        if (!$companyNoticePermission->check() && !$teamNoticePermission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->noticeService->deleteNotice($id);
        return $this->jsonResponse($result);
    }

    /**
     * Retrieves recently published notices
     */
    public function getAdminRecentlyPublishedNotices(Request $request)
    {
        $permission = $this->grantPermission('admin-widgets');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $permittedFields = ["topic", "description"];
        $options = [
            "sorter" => $request->query('sorter', null),
            "pageSize" => 2,
            "current" => 0,
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', ['topic', 'status']),
        ];

        $result = $this->noticeService->getRecentlyPublishedNotices($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /**
     * Retrieves recently published notices
     */
    public function getManagerRecentlyPublishedNotices(Request $request)
    {
        $permission = $this->grantPermission('manager-widgets');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $permittedFields = ["topic", "description"];
        $options = [
            "sorter" => $request->query('sorter', null),
            "pageSize" => 2,
            "current" => 0,
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', ['topic', 'status']),
        ];

        $result = $this->noticeService->getRecentlyPublishedNotices($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /**
     * Retrieves recently published notices
     */
    public function getEmployeeRecentlyPublishedNotices(Request $request)
    {
        $permission = $this->grantPermission('employee-widgets');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $permittedFields = ["topic", "description"];
        $options = [
            "sorter" => $request->query('sorter', null),
            "pageSize" => 2,
            "current" => 0,
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', ['topic', 'status']),
        ];

        $result = $this->noticeService->getRecentlyPublishedNotices($permittedFields, $options);
        return $this->jsonResponse($result);
    }
}
