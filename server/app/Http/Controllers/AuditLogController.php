<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuditLogService;

/*
    Name: AuditLogController
    Purpose: Performs request handling tasks related to the AuditLog model.
    Description: API requests related to the AuditLog model are directed to this controller.
    Module Creator: Chalaka
*/

class AuditLogController extends Controller
{
    protected $auditLogService;

    /**
     * AuditLogController constructor.
     *
     * @param AuditLogService $auditLogService
     */
    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService  = $auditLogService;
    }


    /**
     * Retrives all AuditLogs
     */
    public function index()
    {
        $result = $this->auditLogService->getAllAuditLogs();
        return $this->jsonResponse($result);
    }


    /**
     * Retrives all AuditLogs
     */
    public function showByEmployeeId($id, Request $request)
    {
        $result = $this->auditLogService->getAuditLogsByEmployeeId($id);
        return $this->jsonResponse($result);
    }
}
