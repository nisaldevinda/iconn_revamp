<?php

namespace App\Services;

use Log;
use Exception;
use App\Library\Store;
use App\Library\Util;
use Illuminate\Support\Facades\Lang;
use App\Library\ModelValidator;
use App\Library\Session;
use App\Traits\JsonModelReader;
use App\Traits\ConfigHelper;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use App\Traits\EmployeeHelper;

/**
 * Name: LeaveTypeService
 * Purpose: Performs tasks related to the Leave Type model.
 * Description: Leave Type Service class is called by the LeaveTypeController where the requests related
 * to User Leave Type Model (CRUD operations and others).
 * Module Creator: Tharindu Darshana
 */
class LeaveTypeService extends BaseService
{
    use JsonModelReader;
    use ConfigHelper;
    use EmployeeHelper;

    private $store;
    protected $session;

    private $leaveTypeModel;
    private $leaveEntitlementModel;

    public function __construct(Store $store, Session $session)
    {
        $this->store = $store;
        $this->session = $session;

        $this->leaveTypeModel = $this->getModel('leaveType', true);
        $this->leaveEntitlementModel = $this->getModel('leaveEntitlement', true);
    }

    /**
     * Following function creates a leave type. The leave type details that are provided in the Request
     * are extracted and saved to the leave type table in the database. leave_type_id is auto genarated 
     *
     * @param $leaveType array containing the leave type data
     * @return int | String | array
     *
     * Usage:
     * $leaveType => [
     *
     * ]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Leave Type created successfully!",
     * $data => {"title": "LK HR", ...} //$data has a similar set of values as the input
     *  */

    public function createLeaveType($leaveType)
    {
        try {

            $validationResponse = ModelValidator::validate($this->leaveTypeModel, $leaveType, false);
            if (!empty($validationResponse)) {
                if (isset($validationResponse['name'])) {
                    $data = [
                        'name' => [Lang::get('leaveTypeMessages.basic.LEAVE_TYPE_EXIST')]
                    ];
                    return $this->error(400, Lang::get('leaveTypeMessages.basic.ERR_CREATE'), $data);
                }
                return $this->error(400, Lang::get('leaveTypeMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $whoCanApply = [
                "jobTitles" => [],
                "employmentStatuses" => [],
                "genders" => [],
                "locations" =>  [],
                "minServicePeriod" =>  null,
                "minPemenancyPeriod" =>  null,
                "custom" => []
            ];

            $leaveType['whoCanApply'] = json_encode($whoCanApply);
            $leaveType['isAllEmployeesCanApply'] = true;


            $newLeaveType = $this->store->insert($this->leaveTypeModel, $leaveType, true);

            return $this->success(201, Lang::get('leaveTypeMessages.basic.SUCC_CREATE'), $newLeaveType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveTypeMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all leave types.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All leave types retrieved Successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function getAllLeaveTypes($permittedFields, $options)
    {
        try {
            $filteredLeaveTypes = $this->store->getAll(
                $this->leaveTypeModel,
                $permittedFields,
                $options,
                [],
                [['isDelete', '=', false]]
            );

            return $this->success(200, Lang::get('leaveTypeMessages.basic.SUCC_GETALL'), $filteredLeaveTypes);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveTypeMessages.basic.ERR_GETALL'), null);
        }
    }

    /**
     * Following function retrives a single leave type for a provided leave_type_id.
     *
     * @param $id user leave type id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Leave Type retrieved Successfully!",
     *      $data => {"title": "LK HR", ...}
     * ]
     */
    public function getLeaveType($id)
    {
        try {
            $leaveType = $this->store->getById($this->leaveTypeModel, $id);
            $leaveTypeWorkingDays = $this->store->getFacade()::table('leaveTypeWorkingDayTypes')
                ->select(['dayTypeId'])
                ->where("leaveTypeId", $id)
                ->pluck('dayTypeId');
            if (!is_null($leaveTypeWorkingDays)) {
                $leaveType->leaveTypeWorkingDays = $leaveTypeWorkingDays;
            }
            if (is_null($leaveType)) {
                return $this->error(404, Lang::get('leaveTypeMessages.basic.ERR_NOT_EXIST'), null);
            }

            $leaveTypeRelateEntitilementsCount = $this->store->getFacade()::table('leaveEntitlement')->select(['id'])->where("leaveTypeId", $id)->count();

            if (!is_null($leaveTypeRelateEntitilementsCount) && $leaveTypeRelateEntitilementsCount > 0) {
                $leaveType->isLinkedWithEntitlement = true;
            } else {
                $leaveType->isLinkedWithEntitlement = false;
            }

            $leaveType->whoCanAssign = json_decode($leaveType->whoCanAssign);
            $leaveType->whoCanUseCoveringPerson = json_decode($leaveType->whoCanUseCoveringPerson);

            return $this->success(200, Lang::get('leaveTypeMessages.basic.SUCC_GET'), $leaveType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveTypeMessages.basic.ERR_GET'), null);
        }
    }

    /**
     * Following function updates a leave type.
     *
     * @param $id leave type id
     * @param $leaveType array containing leave type data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Leave Type updated Successfully",
     *      $data => {"title": "LK HR", ...} // has a similar set of data as entered to updating user.
     *
     */
    public function updateLeaveType($id, $leaveType)
    {
        try {

            $validationResponse = ModelValidator::validate($this->leaveTypeModel, $leaveType, true);
            if (!empty($validationResponse)) {
                if (isset($validationResponse['name'])) {
                    $data = [
                        'name' => [Lang::get('leaveTypeMessages.basic.LEAVE_TYPE_EXIST')]
                    ];
                    return $this->error(400, Lang::get('leaveTypeMessages.basic.ERR_CREATE'), $data);
                }
                return $this->error(400, Lang::get('leaveTypeMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $existingLeaveType = $this->store->getById($this->leaveTypeModel, $id);
            if (is_null($existingLeaveType)) {
                return $this->error(404, Lang::get('leaveTypeMessages.basic.ERR_NOT_EXIST'), null);
            }

            if (isset($leaveType['leavePeriod']) && $existingLeaveType->leavePeriod != $leaveType['leavePeriod']) {
                $leaveTypeRelateEntitilementsCount = $this->store->getFacade()::table('leaveEntitlement')->select(['id'])->where("leaveTypeId", $id)->count();

                if (!is_null($leaveTypeRelateEntitilementsCount) && $leaveTypeRelateEntitilementsCount > 0) {
                    return $this->error(500, Lang::get('leaveTypeMessages.basic.ERR_CAN_NOT_CHANGE_PERIOD_TYPE'), null);
                }
            }

            if (isset($leaveType["leaveTypeWorkingDays"])) {
                $leaveTypeWorkingDays = $leaveType["leaveTypeWorkingDays"];
                $delete = $this->store->getFacade()::table('leaveTypeWorkingDayTypes')
                    ->where('leaveTypeId', $id)
                    ->delete();

                foreach ($leaveTypeWorkingDays as $value) {
                    $existingData = $this->store->getFacade()::table('leaveTypeWorkingDayTypes')
                        ->insert([
                            'leaveTypeId' => $id,
                            'dayTypeId' => $value
                        ]);
                }
            }
            if (isset($leaveType["whoCanApply"]) && isset($leaveType['isAllEmployeesCanApply']) && !$leaveType['isAllEmployeesCanApply']) {
                $whoCanApply = [
                    "jobTitles" => (!empty($leaveType['whoCanApply']['jobTitles'])) ? $leaveType['whoCanApply']['jobTitles'] : [],
                    "employmentStatuses" => (!empty($leaveType['whoCanApply']['employmentStatuses'])) ? $leaveType['whoCanApply']['employmentStatuses'] : [],
                    "genders" => (!empty($leaveType['whoCanApply']['genders'])) ? $leaveType['whoCanApply']['genders'] : [],
                    "locations" => (!empty($leaveType['whoCanApply']['locations'])) ? $leaveType['whoCanApply']['locations'] : [],
                    "minServicePeriod" => (!empty($leaveType['whoCanApply']['minServicePeriod'])) ? $leaveType['whoCanApply']['minServicePeriod'] : 0,
                    "minPemenancyPeriod" => (!empty($leaveType['whoCanApply']['minPemenancyPeriod'])) ? $leaveType['whoCanApply']['minPemenancyPeriod'] : 0,
                    "custom" => []
                ];

                $leaveType['whoCanApply'] = json_encode($whoCanApply);
            }

            if (isset($leaveType['isAllEmployeesCanApply']) && $leaveType['isAllEmployeesCanApply']) {

                $whoCanApply = [
                    "jobTitles" => [],
                    "employmentStatuses" => [],
                    "genders" => [],
                    "locations" =>  [],
                    "minServicePeriod" =>  null,
                    "minPemenancyPeriod" =>  null,
                    "custom" => []
                ];

                $leaveType['whoCanApply'] = json_encode($whoCanApply);
            }

            if (isset($leaveType["whoCanAssign"])) {
                $leaveType['whoCanAssign'] = json_encode($leaveType['whoCanAssign']);
            }

            if (isset($leaveType["whoCanUseCoveringPerson"])) {
                $leaveType['whoCanUseCoveringPerson'] = json_encode($leaveType['whoCanUseCoveringPerson']);
            }

            // $affected =  $this->store->getFacade()::table('leaveType')
            // ->where('id', $id)
            // ->update(["whoCanApply"=>json_encode($leaveType['whoCanApply'])]);
            $result = $this->store->updateById($this->leaveTypeModel, $id, $leaveType);

            if (!$result) {
                return $this->error(502, Lang::get('leaveTypeMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('leaveTypeMessages.basic.SUCC_UPDATE'), $result);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('leaveTypeMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function delete a leave type.
     *
     * @param $id leave type id
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Leave Type deleted Successfully!",
     *      $data => {"title": "LK HR", ...}
     *
     */
    public function deleteLeaveType($id)
    {
        try {

            $existingLeaveType = $this->store->getById($this->leaveTypeModel, $id);
            if (is_null($existingLeaveType)) {
                return $this->error(404, Lang::get('leaveTypeMessages.basic.ERR_NOT_EXIST'), null);
            }

            $recordExist = Util::checkRecordsExist($this->leaveTypeModel, $id);

            if (!empty($recordExist)) {
                return $this->error(502, Lang::get('leaveTypeMessages.basic.ERR_NOTALLOWED'), null);
            }

            //get leaves that link with this leave type
            $relatedLeaves = $this->store->getFacade()::table('leaveRequest')->where('leaveTypeId', $id)->get();
            if (!is_null($relatedLeaves) && sizeof($relatedLeaves) > 0) {
                return $this->error(400, Lang::get('leaveTypeMessages.basic.ERR_HAS_DEPENDENT_LEAVES'), null);
            }
            
            //check whether leave type is link with leave entitilements
            $relatedEntitlements = $this->store->getFacade()::table('leaveEntitlement')
                ->where('leaveTypeId', $id)
                ->where('isDelete', false)->get();
            
            if (!is_null($relatedEntitlements) && sizeof($relatedEntitlements) > 0) {
                return $this->error(400, Lang::get('leaveTypeMessages.basic.ERR_HAS_DEPENDENT_LEAVE_ENTITLEMENTS'), null);
            }

            $leaveTypeModelName = $this->leaveTypeModel->getName();
            $result = $this->store->getFacade()::table('leaveType')->where('id', $id)->update(['isDelete' => true]);

            if ($result == 0) {
                return $this->error(502, Lang::get('leaveTypeMessages.basic.ERR_DELETE'), $id);
            }

            return $this->success(200, Lang::get('leaveTypeMessages.basic.SUCC_DELETE'), $existingLeaveType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('leaveTypeMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function retrives leave types which can apply by current employee.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Leave types which can apply by current employee retrieved successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function getLeaveTypesForApplyLeave()
    {
        try {
            $employeeId = $this->session->employee->id;
            $data = $this->filterLeaveTypesAgainsEmployeeAndUserRoles($employeeId);


            foreach ($data as $key => $value) {
                $value = (array) $value;

                if ($value['shortLeaveAllowed'] == 1) {
                    $data[$key]->short_leave_duration = $this->getConfigValue('short_leave_duration');
                }

                $data[$key]->canShowCoveringPerson = false;
                if ($value['allowCoveringPerson']) {
                    $useCoveringPersonAllowEmpGroups = (!empty($value['whoCanUseCoveringPerson'])) ? json_decode($value['whoCanUseCoveringPerson']) : [];

                    foreach ($useCoveringPersonAllowEmpGroups as $groupKey1 => $groupId) {
                        // get group employees
                        $employees = $this->getEmployeeIdsByLeaveGroupId($groupId);

                        $employeeIds = [];
                        foreach ($employees as $empKey => $employee) {
                            $employee = (array) $employee;
                            array_push($employeeIds, $employee['id']);
                        }

                        if (in_array($employeeId, $employeeIds)) {
                            $data[$key]->canShowCoveringPerson = true;
                            break;
                        }
                    }
                }
            }

            return $this->success(200, Lang::get('leaveTypeMessages.basic.SUCC_GETALL'), $data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveTypeMessages.basic.ERR_GETALL'), $e->getMessage());
        }
    }

    /**
     * Following function retrives leave types which can assign by current user.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Leave types which can assign by current user retrieved successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function getLeaveTypesForAssignLeave($employeeId = null)
    {
        try {
            $userId = $this->session->user->id;
            $data = $this->filterLeaveTypesAgainsEmployeeAndUserRoles($employeeId, $userId);

            foreach ($data as $key => $value) {
                $value = (array) $value;

                if ($value['shortLeaveAllowed'] == 1) {
                    $data[$key]->short_leave_duration = $this->getConfigValue('short_leave_duration');
                }
            }

            return $this->success(200, Lang::get('leaveTypeMessages.basic.SUCC_GETALL'), $data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveTypeMessages.basic.ERR_GETALL'), $e->getMessage());
        }
    }


    /**
     * Following function retrives leave types which can assign by current user.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Leave types which can assign by current user retrieved successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function getLeaveTypesForAdminApplyLeaveForEmployee($employeeId = null)
    {
        try {
            $data = $this->filterLeaveTypesAgainsEmployeeAndUserRoles($employeeId);

            foreach ($data as $key => $value) {
                $value = (array) $value;

                if ($value['shortLeaveAllowed'] == 1) {
                    $data[$key]->short_leave_duration = $this->getConfigValue('short_leave_duration');
                }
            }

            return $this->success(200, Lang::get('leaveTypeMessages.basic.SUCC_GETALL'), $data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveTypeMessages.basic.ERR_GETALL'), $e->getMessage());
        }
    }

    private function filterLeaveTypesAgainsEmployeeAndUserRoles($employeeId, $userId = null)
    {
        $userRoles = [];
        $employee = null;

        if (!empty($employeeId)) {
            $employee = $this->store->getFacade()::table('employee')
                ->where('id', $employeeId)
                ->first();

            if (!empty($employee->currentJobsId)) {
                $employee->currentJob = $this->store->getFacade()::table('employeeJob')
                    ->where('id', $employee->currentJobsId)
                    ->first();

                $employee->currentEmploymentStatus = $this->store->getFacade()::table('employmentStatus')
                    ->where('id', $employee->currentJob->employmentStatusId)
                    ->first();
            } else {
                $employee->currentJob = null;
                $employee->currentEmploymentStatus = null;
            }
        }

        if (!empty($userId)) {
            $user = $this->store->getFacade()::table('user')
                ->where('id', $userId)
                ->first();

            if (!empty($user)) {
                // if (!empty($user->employeeRoleId)) {
                //     $userRoles[] = $user->employeeRoleId;
                // }

                // if (!empty($user->managerRoleId)) {
                //     $userRoles[] = $user->managerRoleId;
                // }

                if (!empty($user->adminRoleId)) {
                    $userRoles[] = $user->adminRoleId;
                }
            }
        }

        $query = $this->store->getFacade()::table($this->leaveTypeModel->getName());

        // if (!empty($userRoles)) {
        //     $query->whereJsonContains('whoCanAssign', $userRoles);
        // }

        if (!empty($userId)) {
            if (empty($userRoles)) {
                return [];
            } else {
                // $query->where('adminsCanAssign', true);
                $bool = true;

                $query->where('adminsCanAssign', '1');
                $query->whereJsonContains('whoCanAssign', $userRoles);
            }
        } else {
            $query->where('employeesCanApply', true);
            $query->where(function ($subQuery) use ($employee) {
                $subQuery->whereJsonContains('whoCanApply->genders', $employee->genderId)
                    ->orwhereJsonContains('whoCanApply->genders', '*')
                    ->orWhereJsonLength('whoCanApply->genders', 0);
            });

            if (!empty($employee->currentJob)) {
                // consider employment statuses
                $query->where(function ($subQuery) use ($employee) {
                    $subQuery->whereJsonContains('whoCanApply->employmentStatuses', $employee->currentJob->employmentStatusId)
                        ->orwhereJsonContains('whoCanApply->employmentStatuses', '*')
                        ->orWhereJsonLength('whoCanApply->employmentStatuses', 0);
                });

                $company = (array) $this->session->getCompany();
                $companyTimeZone = new DateTimeZone($company["timeZone"] ?? null);
                $today = new DateTime("now", $companyTimeZone);

                // consider minimum service period
                $effectiveDate = !empty($employee->currentJob->effectiveDate)
                    ? new DateTime($employee->currentJob->effectiveDate)
                    : new DateTime("now", $companyTimeZone);
                $servicePeriodDiff = $today->diff($effectiveDate);
                $servicePeriod = $employee->currentJob->employeeJourneyType != 'RESIGNATIONS'
                    ? (12 * $servicePeriodDiff->y) + $servicePeriodDiff->m : 0;
                $query->where(function ($subQuery) use ($servicePeriod) {
                    $subQuery->where('whoCanApply->minServicePeriod', '<=', $servicePeriod);
                });

                // TODO: need to recheck 
                // consider minimum pemenancy period
                switch ($employee->currentEmploymentStatus->periodUnit) {
                    case 'YEARS':
                        $pemenancyPeriod = 12 * $employee->currentEmploymentStatus->period;
                        break;
                    case 'MONTHS':
                        $pemenancyPeriod = $employee->currentEmploymentStatus->period;
                        break;
                    case 'DAYS':
                        $pemenancyPeriod = $employee->currentEmploymentStatus->period / 12;
                        break;
                    default:
                        $pemenancyPeriod = 0;
                        break;
                }
                $query->where(function ($subQuery) use ($pemenancyPeriod) {
                    $subQuery->where('whoCanApply->minPemenancyPeriod', '<=', $pemenancyPeriod);
                });
            } else {
                $query->where(function ($subQuery) use ($employee) {
                    $subQuery->whereJsonLength('whoCanApply->employmentStatuses', 0);
                });

                $query->where(function ($subQuery) {
                    $subQuery->where('whoCanApply->minServicePeriod', '=', 0);
                });

                $query->where(function ($subQuery) {
                    $subQuery->where('whoCanApply->minPemenancyPeriod', '=', 0);
                });
            }

            if (!empty($employee->currentJob)) {
                // consider job titles
                $query->where(function ($subQuery) use ($employee) {
                    $subQuery->whereJsonContains('whoCanApply->jobTitles', $employee->currentJob->jobTitleId)
                        ->orwhereJsonContains('whoCanApply->jobTitles', '*')
                        ->orWhereJsonLength('whoCanApply->jobTitles', 0);
                });

                // consider locations
                $query->where(function ($subQuery) use ($employee) {
                    $subQuery->whereJsonContains('whoCanApply->locations', $employee->currentJob->locationId)
                        ->orwhereJsonContains('whoCanApply->locations', '*')
                        ->orWhereJsonLength('whoCanApply->locations', 0);
                });
            }
        }

        $query->where('leaveType.isDelete', false);
        return $query->get();
    }

    public function getEmployeeEntitlementCount($date, $employeeId, $leaveType = null, $accessLevel = 'Apply-Leave')
    {
        try {

            $isFromApplyLeave = ($accessLevel == 'Apply-Leave') ? true : false;

            if (empty($date) || !DateTime::createFromFormat('Y-m-d', $date) !== false) {
                $company = (array) $this->session->getCompany();
                $companyTimeZone = $company["timeZone"] ?? null;
                $companyDateObject = new DateTime("now", new DateTimeZone($companyTimeZone));
                $date = $companyDateObject->format('Y-m-d');
            }

            if (is_null($employeeId)) {
                $employeeId = $this->session->employee->id;
                // $isFromApplyLeave = true;
            }
            $data = $this->store->getFacade()::table($this->leaveEntitlementModel->getName())
                ->leftJoin($this->leaveTypeModel->getName(), $this->leaveTypeModel->getName() . '.id', '=', $this->leaveEntitlementModel->getName() . '.leaveTypeId')
                ->where($this->leaveEntitlementModel->getName() . '.employeeId', $employeeId)
                ->where($this->leaveEntitlementModel->getName() . '.validTo', '>=', $date)
                ->where($this->leaveEntitlementModel->getName() . '.validFrom', '<=', $date)
                ->where($this->leaveEntitlementModel->getName() . '.isDelete', false);


            if ($isFromApplyLeave) {
                $data = $data->where($this->leaveTypeModel->getName() . '.employeesCanApply', true);
            } else {
                $userId = $this->session->user->id;
                $userRoles = [];
                $user = $this->store->getFacade()::table('user')
                    ->where('id', $userId)
                    ->first();

                if (!empty($user)) {
                    if (!empty($user->adminRoleId)) {
                        $userRoles[] = $user->adminRoleId;
                    }
                }


                $data = $data->where($this->leaveTypeModel->getName() . '.adminsCanAssign', true);
                $data = $data->whereJsonContains($this->leaveTypeModel->getName() .'.whoCanAssign', $userRoles);
            }

            if (!empty($leaveType)) {
                $data->where($this->leaveEntitlementModel->getName() . '.leaveTypeId', '=', $leaveType);
            }

            $data->groupBy($this->leaveEntitlementModel->getName() . '.leaveTypeId')
                ->selectRaw($this->leaveTypeModel->getName() . '.name, sum(' . $this->leaveEntitlementModel->getName() . '.entilementCount) as total, sum(' . $this->leaveEntitlementModel->getName() . '.pendingCount) as pending, sum(' . $this->leaveEntitlementModel->getName() . '.usedCount) as used ,' . $this->leaveTypeModel->getName() . '.leaveTypeColor, ' . $this->leaveTypeModel->getName() . '.id as leaveTypeID');
            $data = $data->get();

            if ($isFromApplyLeave) {
                $allowLeaveTypesData = $this->filterLeaveTypesAgainsEmployeeAndUserRoles($employeeId);
            } else {
                $userId = $this->session->user->id;
                $allowLeaveTypesData = $this->filterLeaveTypesAgainsEmployeeAndUserRoles($employeeId, $userId);
            }

            $allowLeaveTypes = [];
            foreach ($allowLeaveTypesData as $leaveTypekey => $leaveType) {
                $leaveType = (array) $leaveType;

                $allowLeaveTypes[] = $leaveType['name'];
            }

            $entitlementDataSet = [];
            foreach ($data as $entitlementDatakey => $entitlementData) {
                $entitlementData = (array) $entitlementData;
               
               $leaveTypeAllowExceedingBalance = $this->store->getFacade()::table($this->leaveTypeModel->getName())
                  ->where('id',$entitlementData['leaveTypeID'])
                  ->where('allowExceedingBalance' ,true)
                  ->first();

               //if leaveType allow exceeding Balance
               if (!empty($leaveTypeAllowExceedingBalance)) {
                   $exceedingBalance = $this->store->getFacade()::table('leaveRequest')
                       ->leftJoin('leaveRequestDetail', 'leaveRequest.id', '=', 'leaveRequestDetail.leaveRequestId')
                       ->leftJoin('leaveRequestEntitlement', 'leaveRequestDetail.id', '=', 'leaveRequestEntitlement.leaveRequestDetailId')
                       ->whereNull('leaveRequestEntitlement.leaveEntitlementId')
                       ->where('leaveRequest.employeeId', $employeeId)
                       ->where('leaveRequest.leaveTypeId',$leaveTypeAllowExceedingBalance->id)
                       ->count();

                    $entitlementData['exceeding'] =  $exceedingBalance;
                }

                if (in_array($entitlementData['name'], $allowLeaveTypes)) {
                    $entitlementDataSet[] = $entitlementData;
                }
                
            }

            return $this->success(200, Lang::get('leaveTypeMessages.basic.SUCC_GETALL'), $entitlementDataSet);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveTypeMessages.basic.ERR_GETALL'), $e->getMessage());
        }
    }

    /**
     * Following function retrives all leave types.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All leave types retrieved Successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function getLeaveTypesList()
    {
        try {
            $employee = $this->store->getFacade()::table('leaveType')
                ->select(["*"])
                ->orderBy("name", "ASC")
                ->where("isDelete", false)
                ->get();
            return $this->success(200, Lang::get('leaveTypeMessages.basic.SUCC_GETALL'), $employee);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveTypeMessages.basic.ERR_GETALL'), null);
        }
    }

    /**
     * Following function retrives all leave types working days.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All leave types retrieved Successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function getLeaveTypesWorkingDays()
    {
        try {
            $leaveType = $this->store->getFacade()::table('workCalendarDayType')
                ->select(["*"])
                ->where("id", '!=', 1)
                ->where("isDelete", false)
                ->get();
            if (is_null($leaveType)) {
                return $this->error(404, Lang::get('leaveTypeMessages.basic.ERR_NOT_EXIST'), null);
            }
            return $this->success(200, Lang::get('leaveTypeMessages.basic.SUCC_GETALL'), $leaveType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveTypeMessages.basic.ERR_GETALL'), null);
        }
    }
}
