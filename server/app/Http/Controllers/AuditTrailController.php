<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuditTrailService;

/*
    Name: AuditTrailController
    Purpose: Performs request handling tasks related to the Audit Trail model.
    Description: API requests related to the Audit Trail model are directed to this controller.
    Module Creator: Hashan
*/

class AuditTrailController extends Controller
{
    protected $auditTrailService;

    /**
     * AuditTrailController constructor.
     *
     * @param AuditTrailService $auditTrailService
     */
    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService  = $auditTrailService;
    }

    /**
     * Retrives all Audit Trail
     */
    public function getAll(Request $request)
    {
        $permission = $this->grantPermission('reports-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $permittedFields = ["*"];
        $options = [
            "current" => $request->query('current', null),
            "pageSize" => $request->query('pageSize', null),
            "orgStructureEntityId" => $request->query('orgStructureEntityId', null),
            "startDate" => $request->query('startDate', null),
            "endDate" => $request->query('endDate', null),
            "sorter" => $request->query('sorter', null),
        ];

        $result = $this->auditTrailService->getAllAuditLogs($permittedFields, $options);
        return $this->jsonResponse($result);
    }
}
