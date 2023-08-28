<?php

namespace App\Services;

use App\Library\Interfaces\ModelReaderInterface;
use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use Illuminate\Support\Facades\Hash;
use App\Library\Store;
use App\Library\AuditLogger;
use App\Traits\JsonModelReader;

/**
 * Name: AuditLogService
 * Purpose: Performs tasks related to the AuditLog model.
 * Description: AuditLog Service class is called by the AuditLogController where the requests related
 * to AuditLog Model (basic operations and others). Table that is being modified is auditLog.
 * Module Creator: Chalaka 
 */
class AuditLogService extends BaseService
{
    use JsonModelReader;

    private $auditLogger;

    public function __construct(Store $store, ModelReaderInterface $auditLogReader)
    {
        $this->store = $store;
        $this->auditLogModel = $auditLogReader->getModel('auditLog');
        $this->auditLogger = new AuditLogger();
    }


    /** 
     * Following function retrives all auditLogs.
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "auditLog created Successuflly",
     *      $data => {{"id": 1, name": "Male"}, {"id": 1, name": "Male"}}
     * ] 
     */
    public function getAllAuditLogs()
    {
        try {
            $auditLogs = $this->auditLogger->retrieveAllAuditLogs();
            return $this->success(200, Lang::get('auditLogMessages.basic.SUCC_ALL_RETRIVE'), $auditLogs);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('auditLogMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }


    /** 
     * Following function retrives all auditLogs for an employee.
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "auditLog created Successuflly",
     *      $data => {{"id": 1, name": "Male"}, {"id": 1, name": "Male"}}
     * ] 
     */
    public function getAuditLogsByEmployeeId($employeeId)
    {
        try {
            $auditLogs = $this->auditLogger->retrieveAuditLogsByEmployeeId($employeeId);
            return $this->success(200, Lang::get('auditLogMessages.basic.SUCC_ALL_RETRIVE'), $auditLogs);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('auditLogMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
}