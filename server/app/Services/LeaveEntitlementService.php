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
use App\Library\Session;
use Illuminate\Support\Facades\DB;


/**
 * Name: LeaveEntitlementService
 * Purpose: Performs tasks related to the LeaveEntitlement model.
 * Description: LeaveEntitlement Service class is called by the LeaveEntitlementController where the requests related
 * to LeaveEntitlement Model (basic operations and others). Table that is being modified is leaveEntitlement.
 * Module Creator: Chalaka
 */
class LeaveEntitlementService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $leaveEntitlementModel;
    private $session;

    public function __construct(Store $store, Session $session)
    {
        $this->store = $store;
        $this->session = $session;
        $this->leaveEntitlementModel = $this->getModel('leaveEntitlement', true);
        $this->employeeModel = $this->getModel('employee', true);
        $this->leaveTypeModel = $this->getModel('leaveType', true);
    }

    /**
     * Following function creates a LeaveEntitlement.
     *
     * @param $LeaveEntitlement array containing the LeaveEntitlement data
     * @return int | String | array
     *
     * Usage:
     * $LeaveEntitlement => ["name": "Male"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "leaveEntitlement created Successuflly",
     * $data => {"name": "Male"}//$data has a similar set of values as the input
     *  */

    public function createLeaveEntitlement($leaveEntitlement)
    {
        try {
            $validationResponse = ModelValidator::validate($this->leaveEntitlementModel, $leaveEntitlement, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('leaveEntitlementMessages.basic.ERR_CREATE'), $validationResponse);
            }
            $leaveTypeId = $leaveEntitlement["leaveTypeId"];
            $leaveType = $this->store->getFacade()::table('leaveType')->where('id', $leaveTypeId)->first();

            if (!$leaveType->adminCanAdjustEntitlements) {
                return $this->error(400, Lang::get('leaveEntitlementMessages.basic.ERR_ADMIN_CANNOT_ADJUST'), $leaveEntitlement);
            }

            $newLeaveEntitlement = $this->store->insert($this->leaveEntitlementModel, $leaveEntitlement, true);

            $this->processOverdrawnLeaves($newLeaveEntitlement);

            return $this->success(201, Lang::get('leaveEntitlementMessages.basic.SUCC_CREATE'), $newLeaveEntitlement);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('leaveEntitlementMessages.basic.ERR_CREATE'), null);
        }
    }

    /**
     * Following function creates a LeaveEntitlement.
     *
     * @param $LeaveEntitlement array containing the LeaveEntitlement data
     * @return int | String | array
     *
     * Usage:
     * $LeaveEntitlement => ["name": "Male"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "leaveEntitlement created Successuflly",
     * $data => {"name": "Male"}//$data has a similar set of values as the input
     *  */

    public function createLeaveEntitlementMultiple($leaveEntitlement)
    {
        try {
            $validationResponse = ModelValidator::validate($this->leaveEntitlementModel, $leaveEntitlement, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('leaveEntitlementMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $leaveTypeId = $leaveEntitlement["leaveTypeId"];
            $leaveType = $this->store->getFacade()::table('leaveType')->where('id', $leaveTypeId)->first();

            if (!$leaveType->adminCanAdjustEntitlements) {
                return $this->error(400, Lang::get('leaveEntitlementMessages.basic.ERR_ADMIN_CANNOT_ADJUST'), $leaveEntitlement);
            }

            $employeeIds = $leaveEntitlement["employeeIds"];
            $newLeaveEntitlements = [];
            if (!empty($employeeIds)) {
                foreach ($employeeIds as $value) {
                    $leaveEntitlement["employeeId"] = $value;
                    Log::error(array($leaveEntitlement));

                    $newLeaveEntitlement = $this->store->insert($this->leaveEntitlementModel, $leaveEntitlement, true);
                    $newLeaveEntitlements[] = $newLeaveEntitlement;
                    $this->processOverdrawnLeaves($newLeaveEntitlement);
                }
            }

            return $this->success(201, Lang::get('leaveEntitlementMessages.basic.SUCC_CREATE'), $newLeaveEntitlements);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('leaveEntitlementMessages.basic.ERR_CREATE'), null);
        }
    }

    /**
     * Following function retrives all leaveEntitlements.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "leaveEntitlement created Successuflly",
     *      $data => {{"id": 1, name": "Male"}, {"id": 1, name": "Male"}}
     * ]
     */
    public function getAllLeaveEntitlements($permittedFields, $options)
    {
        try {
            $op = [
                "sorter" => null,
                "pageSize" => null,
                "current" => null,
                "keyword" => null,
                "searchFields" => null
            ];
            $filterData = json_decode($options["filter"]);
            $customWhereClause = [];

            if (property_exists($filterData, "employeeId")) {
                array_push($customWhereClause, ['employeeId', '=', $filterData->employeeId]);
            }

            if (property_exists($filterData, "leaveTypeId")) {
                array_push($customWhereClause, ['leaveTypeId', '=', $filterData->leaveTypeId]);
            }

            //get the leavePeriodFrom and leavePeriodTo data for the selected leave period range
            if (property_exists($filterData, "id")) {
                $leaveEntitlement = $this->store->getFacade()::table('leaveEntitlement')->where('id', $filterData->id)->first(['leavePeriodFrom', 'leavePeriodTo']);

                array_push($customWhereClause, ['leavePeriodFrom', '=', $leaveEntitlement->leavePeriodFrom]);
                array_push($customWhereClause, ['leavePeriodTo', '=', $leaveEntitlement->leavePeriodTo]);
            }
            array_push($customWhereClause, ['isDelete', '=', 0]);
            $filteredLeaveEntitlements = $this->store->getAll(
                $this->leaveEntitlementModel,
                $permittedFields,
                $op,
                [],
                $customWhereClause

            );

            return $this->success(200, Lang::get('leaveEntitlementMessages.basic.SUCC_ALL_RETRIVE'), $filteredLeaveEntitlements);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('leaveEntitlementMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single LeaveEntitlement for a provided id.
     *
     * @param $id leaveEntitlement id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Marital Status created Successuflly",
     *      $data => {"id": 1, name": "Male"}
     * ]
     */
    public function getLeaveEntitlement($id)
    {
        try {
            $leaveEntitlement = $this->store->getFacade()::table('leaveEntitlement')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($leaveEntitlement)) {
                return $this->error(404, Lang::get('leaveEntitlementMessages.basic.ERR_NONEXISTENT_GENDER'), $leaveEntitlement);
            }

            return $this->success(200, Lang::get('leaveEntitlementMessages.basic.SUCC_SINGLE_RETRIVE'), $leaveEntitlement);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('leaveEntitlementMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single leaveEntitlement for a provided id.
     *
     * @param $id leaveEntitlement id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "leaveEntitlement created Successuflly",
     *      $data => {"id": 1, name": "Male"}
     * ]
     */
    public function getLeaveEntitlementByKeyword($keyword)
    {
        try {
            $leaveEntitlement = $this->store->getFacade()::table('leaveEntitlement')->where('name', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('leaveEntitlementMessages.basic.SUCC_ALL_RETRIVE'), $leaveEntitlement);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('leaveEntitlementMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }



    /**
     * Following function updates a leaveEntitlement.
     *
     * @param $id leaveEntitlement id
     * @param $LeaveEntitlement array containing LeaveEntitlement data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "leaveEntitlement updated successfully.",
     *      $data => {"id": 1, name": "Male"} // has a similar set of data as entered to updating LeaveEntitlement.
     *
     */
    public function updateLeaveEntitlement($id, $leaveEntitlement)
    {
        try {
            $validationResponse = ModelValidator::validate($this->leaveEntitlementModel, $leaveEntitlement, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('leaveEntitlementMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            $leaveTypeId = $leaveEntitlement["leaveTypeId"];
            $leaveType = $this->store->getFacade()::table('leaveType')->where('id', $leaveTypeId)->first();

            if (!$leaveType->adminCanAdjustEntitlements) {
                return $this->error(400, Lang::get('leaveEntitlementMessages.basic.ERR_UPDATE_ADMIN_CANNOT_ADJUST'), $leaveEntitlement);
            }
            $dbLeaveEntitlement = $this->store->getFacade()::table('leaveEntitlement')->where('id', $id)->first();
            if (is_null($dbLeaveEntitlement)) {
                return $this->error(404, Lang::get('leaveEntitlementMessages.basic.SUCC_UPDATE'), $leaveEntitlement);
            }
            $utilizedCount = $dbLeaveEntitlement->pendingCount + $dbLeaveEntitlement->usedCount;
           
            if ($leaveEntitlement['entilementCount'] < $utilizedCount) {
                return $this->error(400, Lang::get('leaveEntitlementMessages.basic.ERR_UPDATE_ENTITLEMENT_COUNT'), null);
            }
            $result = $this->store->updateById($this->leaveEntitlementModel, $id, $leaveEntitlement);

            if (!$result) {
                return $this->error(502, Lang::get('leaveEntitlementMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('leaveEntitlementMessages.basic.SUCC_UPDATE'), $leaveEntitlement);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('leaveEntitlementMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id leaveEntitlement id
     * @param $LeaveEntitlement array containing LeaveEntitlement data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "leaveEntitlement deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteLeaveEntitlement($id)
    {
        try {
            $leaveEntitlement = $this->store->getById($this->leaveEntitlementModel, $id);
            if (is_null($leaveEntitlement)) {
                return $this->error(404, Lang::get('leaveEntitlementMessages.basic.ERR_NOT_EXIST'), null);
            }

            $recordExist = Util::checkRecordsExist($this->leaveEntitlementModel, $id);
            if (!empty($recordExist)) {
                return $this->error(502, Lang::get('leaveEntitlementMessages.basic.ERR_NOTALLOWED'), null);
            }

            //if leave request is in pending or approved status should not allow the user to delete the leave entitlement
            if ($leaveEntitlement->pendingCount !=0 || $leaveEntitlement->usedCount !=0) {
                return $this->error(400, Lang::get('leaveEntitlementMessages.basic.ERR_DELETE_NOTALLOWED'), null);
            }
           
            $this->store->getFacade()::table('leaveEntitlement')->where('id', $id)->update(['isDelete' => true]);
            return $this->success(200, Lang::get('leaveEntitlementMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('leaveEntitlementMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a leaveEntitlement.
     *
     * @param $id leaveEntitlement id
     * @param $LeaveEntitlement array containing LeaveEntitlement data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "leaveEntitlement deleted successfully.",
     *      $data => null
     *
     */
    public function hardDeleteLeaveEntitlement($id)
    {
        try {
            $dbLeaveEntitlement = $this->store->getById($this->leaveEntitlementModel, $id);
            if (is_null($dbLeaveEntitlement)) {
                return $this->error(404, Lang::get('leaveEntitlementMessages.basic.ERR_NONEXISTENT_GENDER'), null);
            }

            $this->store->deleteById($this->leaveEntitlementModel, $id);

            return $this->success(200, Lang::get('leaveEntitlementMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('leaveEntitlementMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function existing employees.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All employees retrieved Successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function getExistingEmployees()
    {
        try {
            // get permitted employee ids
            $entitlementModelName = $this->leaveEntitlementModel->getName();
            $employeeModelName = $this->employeeModel->getName();
            $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();

            $employees = DB::table($entitlementModelName)
                ->select(DB::raw("CONCAT(firstName,' ' ,lastName) AS label"), "employeeId as value")
                ->leftJoin($employeeModelName, 'employee.id', '=', 'leaveEntitlement.employeeId')
                //->whereIn("id", $permittedEmployeeIds)
                ->orderBy('label', 'asc')
                ->distinct()
                ->get();

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $employees);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }

    /**
     * Following function existing leavTypes.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All employees retrieved Successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function getExistingLeaveTypes()
    {
        try {
            $entitlementModelName = $this->leaveEntitlementModel->getName();
            $leaveTypeModel = $this->employeeModel->getName();
            $leaveType = DB::table($entitlementModelName)
                ->select("leaveType.name as label", "leaveEntitlement.leaveTypeId as value")
                ->leftJoin("leaveType", 'leaveType.id', '=', 'leaveEntitlement.leaveTypeId')
                ->distinct()
                ->orderBy('leaveType.name', 'ASC')
                ->get();

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $leaveType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }

    /**
     * Following function existing leavTypes.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All employees retrieved Successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function getExistingLeavePeriods($options)
    {
        try {
            $entitlementModelName = $this->leaveEntitlementModel->getName();
            Log::error($options["employeeId"]);

            $periods = DB::table($entitlementModelName)
                ->select(DB::raw("CONCAT((DATE_FORMAT(leavePeriodFrom, '%d-%m-%Y')), ' to ' ,(DATE_FORMAT(leavePeriodTo , '%d-%m-%Y'))) AS label"), "id as value")
                ->where([['leaveEntitlement.leaveTypeId', '=', $options["leaveTypeId"]], ['leaveEntitlement.employeeId', '=', $options["employeeId"]]])
                ->groupBy("label")
                ->orderBy('label', 'asc')
                ->get();

            return $this->success(200, Lang::get('employeeMessages.basic.SUCC_GETALL'), $periods);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employeeMessages.basic.ERR_GETALL'), null);
        }
    }

    /**
     * Following function check whether employee have entitilement for given leave period
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All employees retrieved Successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function checkEntitlementAvailability($data)
    {
        try {

            $period = DB::table('leaveEntitlement')
            ->select('*')
                ->where('leaveEntitlement.leaveTypeId', $data['leaveTypeId'])
                ->where('employeeId', $data['employeeId'])
                ->where('leaveEntitlement.leavePeriodFrom', '=', $data["from"])
                ->where('leaveEntitlement.leavePeriodTo', '=', $data["to"])
                ->first();

            return $this->success(200, Lang::get('leaveEntitlementMessages.basic.SUCC_CHECK_AVAILABILITY'), $period);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('leaveEntitlementMessages.basic.ERR_CHECK_AVAILABILITY'), null);
        }
    }


    /**
     * Following function retrives all leaveEntitlements.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "leaveEntitlement created Successuflly",
     *      $data => {{"id": 1, name": "Male"}, {"id": 1, name": "Male"}}
     * ]
     */
    public function getMyLeaveEntitlements()
    {
        try {
            $op = [
                "sorter" => null,
                "pageSize" => null,
                "current" => null,
                "keyword" => null,
                "searchFields" => null
            ];
            $employeeId = $this->session->user->employeeId;
            $filteredLeaveEntitlements = $this->store->getAll(
                $this->leaveEntitlementModel,
                ["*"],
                $op,
                [],
                [['employeeId', '=', $employeeId]]

            );
            return $this->success(200, Lang::get('leaveEntitlementMessages.basic.SUCC_ALL_RETRIVE'), $filteredLeaveEntitlements);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('leaveEntitlementMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function handle entitlement allocation to be done when overdrawn leave are there upon adding new entitlements.
     *
     * @param $leaveEntitlementRecord - newly created leaveEntitlement table record
     * @return void
     *
     */
    public function processOverdrawnLeaves($leaveEntitlementRecord)
    {
        try {
            $this->store->getFacade()::beginTransaction();

            $overdrawnLeaveEntitlements = $this->store->getFacade()::table('leaveRequest')
                ->leftJoin('leaveRequestDetail', 'leaveRequest.id', '=', 'leaveRequestDetail.leaveRequestId')
                ->leftJoin('leaveRequestEntitlement', 'leaveRequestDetail.id', '=', 'leaveRequestEntitlement.leaveRequestDetailId')
                ->whereNull('leaveRequestEntitlement.leaveEntitlementId')
                ->where('leaveRequest.employeeId', $leaveEntitlementRecord['employeeId'])
                ->where('leaveRequest.leaveTypeId', $leaveEntitlementRecord['leaveTypeId'])
                ->whereBetween('leaveRequestDetail.leaveDate', [$leaveEntitlementRecord['validFrom'], $leaveEntitlementRecord['validTo']])
                ->whereIn('leaveRequestDetail.status', ['PENDING', 'APPROVED'])
                ->orderBy('leaveRequestDetail.leaveDate')
                ->select(
                    'leaveRequestEntitlement.id AS leaveRequestEntitlementId',
                    'leaveRequestEntitlement.entitlePortion AS leaveRequestEntitlementEntitlePortion',
                    'leaveRequestDetail.status AS leaveRequestDetailStatus'
                )
                ->get();

            $availableEntilementCount = $leaveEntitlementRecord['entilementCount'];
            $usedEntilementCount = 0;
            $pendingEntilementCount = 0;
            if (!$overdrawnLeaveEntitlements->isEmpty()) {
                foreach ($overdrawnLeaveEntitlements as $overdrawnEntitlement) {
                    if ($overdrawnEntitlement->leaveRequestEntitlementEntitlePortion <= $availableEntilementCount) {
                        $availableEntilementCount -= $overdrawnEntitlement->leaveRequestEntitlementEntitlePortion;

                        $overdrawnEntitlement->leaveRequestDetailStatus == 'APPROVED'
                            ? $usedEntilementCount += $overdrawnEntitlement->leaveRequestEntitlementEntitlePortion
                            : $pendingEntilementCount += $overdrawnEntitlement->leaveRequestEntitlementEntitlePortion;

                        $this->store->getFacade()::table('leaveRequestEntitlement')
                            ->where('id', $overdrawnEntitlement->leaveRequestEntitlementId)
                            ->update(['leaveEntitlementId' => $leaveEntitlementRecord['id']]);
                    }
                }

                if ($usedEntilementCount > 0 || $pendingEntilementCount > 0) {
                    $this->store->getFacade()::table('leaveEntitlement')
                        ->where('id', $leaveEntitlementRecord['id'])
                        ->update([
                            'usedCount' => $usedEntilementCount,
                            'pendingCount' => $pendingEntilementCount
                        ]);
                }
            }

            $this->store->getFacade()::commit();
        } catch (Exception $e) {
            Log::error('processOverdrawnLeaves fail > ' . $e->getMessage());
            $this->store->getFacade()::rollback();
        }
    }
}
