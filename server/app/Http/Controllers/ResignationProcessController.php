<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ResignationProcessService;

class ResignationProcessController extends Controller
{
    protected $resignationProcessService;

    public function __construct(ResignationProcessService $resignationProcessService)
    {
        $this->resignationProcessService  = $resignationProcessService;
    }

    public function store(Request $request)
    {
        $permission = $this->grantPermission('config-resignation-process-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        return $this->resignationProcessService->createResignationProcess($data);
    }

    public function index(Request $request)
    {
        $permission = $this->grantPermission('config-resignation-process-read-write');

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
            "searchFields" => $request->query('search_fields', null),
        ];
        return $this->resignationProcessService->getResignationProcessList($permittedFields, $options);
    }

    public function getById($id)
    {
        $permission = $this->grantPermission('config-resignation-process-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        return $this->resignationProcessService->getResignationProcess($id);
    }

    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('config-resignation-process-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        return $this->resignationProcessService->updateResignationProcess($id, $request->all());
    }

    public function delete($id)
    {

        $permission = $this->grantPermission('config-resignation-process-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        return $this->resignationProcessService->deleteResignationProcess($id);
    }
}
