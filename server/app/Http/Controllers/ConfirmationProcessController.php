<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ConfirmationProcessService;

class ConfirmationProcessController extends Controller
{
    protected $confirmationProcessService;

    public function __construct(ConfirmationProcessService $confirmationProcessService)
    {
        $this->confirmationProcessService  = $confirmationProcessService;
    }

    public function store(Request $request)
    {
        $permission = $this->grantPermission('config-confirmation-process-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        return $this->confirmationProcessService->createConfirmationProcess($data);
    }

    public function index(Request $request)
    {
        $permission = $this->grantPermission('config-confirmation-process-read-write');

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
        return $this->confirmationProcessService->getConfirmationProcessList($permittedFields, $options);
    }

    public function getById($id)
    {
        $permission = $this->grantPermission('config-confirmation-process-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        return $this->confirmationProcessService->getConfirmationProcess($id);
    }

    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('config-confirmation-process-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        return $this->confirmationProcessService->updateConfirmationProcess($id, $request->all());
    }

    public function delete($id)
    {

        $permission = $this->grantPermission('config-confirmation-process-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        return $this->confirmationProcessService->deleteConfirmationProcess($id);
    }
}
