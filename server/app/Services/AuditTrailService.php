<?php

namespace App\Services;

use App\Library\Interfaces\ModelReaderInterface;
use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Library\Util;
use App\Traits\JsonModelReader;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Name: AuditTrailService
 * Purpose: Performs tasks related to the Audit Trail model.
 * Description: Audit Trail Service class is called by the AuditTrailController where the requests related
 * to Audit Trail Model (basic operations and others). Table that is being modified is auditTrail.
 * Module Creator: Hashan Hirosh
 */
class AuditTrailService extends BaseService
{
    use JsonModelReader;

    private $store;
    private $auditTrailModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->auditTrailModel = $this->getModel('auditLog', true);
    }

    /**
     * Following function retrives all auditLogs.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All Audit Log retrieved Successuflly",
     *      $data => {{"id": 1, ...}, ...}
     * ]
     */
    public function getAllAuditLogs($permittedFields, $options)
    {
        try {
            $page = (int) ($options["current"] > 0 ? (int) $options["current"] - 1 : 0) * $options["pageSize"];

            $query = $this->store->getFacade()::table('auditLog')
                ->leftJoin('user', 'user.id', '=', 'auditLog.userId')
                ->leftJoin('employee', 'employee.id', '=', 'auditLog.employeeId')
                ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId');

            if (!is_null($options['orgStructureEntityId'])) {
                $query->where('employeeJob.orgStructureEntityId', $options['orgStructureEntityId']);
                $query->whereNotNull('auditLog.employeeId');
            }

            if (!is_null($options['startDate']) && !is_null($options['endDate'])) {
                $query->whereBetween('auditLog.timestamp', array(
                    Carbon::parse($options['startDate'])->startOfDay(),
                    Carbon::parse($options['endDate'])->endOfDay()
                ));
            }

            $total = $query->count();

            $query->limit($options['pageSize'])
                ->offset($page)
                ->orderBy('auditLog.timestamp', 'DESC')
                ->select([
                    'auditLog.*',
                    DB::raw("CONCAT_WS(' ', user.firstName, user.middleName, user.lastName) AS userName"),
                    DB::raw("CONCAT_WS(' ', employee.firstName, employee.middleName, employee.lastName) AS employeeName"),
                ]);

            $response = [
                'total' => $total,
                'data' => $query->get()
            ];

            return $this->success(200, Lang::get('auditLogMessages.basic.SUCC_ALL_RETRIVE'), $response);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('auditLogMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
}
