<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Jobs\BackDatedAttendanceProcess;
use App\Library\Store;
use App\Library\Session;
use App\Traits\JsonModelReader;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;


/**
 * Name:  ShiftAssignService
 * Purpose: Performs tasks related to the  ShiftAssign model.
 * Description:  ShiftAssignService class is called by the   ShiftAssignController where the requests related
 * to ShiftAssign Model (basic operations and others). Table that is being modified is ShiftAssign.
 * Module Creator: Shobana
 */
class ShiftAssignService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $session;

    private $shiftAssignModel;
  
    public function __construct(Store $store, Session $session)
    {
        $this->store = $store;
        $this->session = $session;
        
        
    }

    /**
     * Following function create a Assigning Shift.
     * 
     * @param $data array of shiftAssign data
     * 
     * Usage:
     * $data => {
     *    'workshiftId' => 1,
     *    'selectedEmployees' => [2,4],
     *    'effectiveDate' =>  '2022-02-05'
     * 
     * }
     * 
     * Sample output:
     * $statusCode => 201,
     * $message => "shiftAssign created Successuflly",
     * $data => {"workshiftId": "1", employeeId: [4,5]}
     *  */

    public function createshiftAssign($data)
    {
        try {
            $shiftAssign = [];
            $employeeIds =  isset($data['selectedEmployees']) ? $data['selectedEmployees'] : [];
           
            $assignedEmployeeIds = $this->store->getFacade()::table('employeeShift')
               ->where('employeeShift.workShiftId', $data['shiftId'])
               ->pluck('employeeId')->toArray();
            
            $newEmployeeIds = array_diff($employeeIds , $assignedEmployeeIds);
            $removeOldEmployeeIds = array_diff($assignedEmployeeIds ,$employeeIds );

            if (!empty($newEmployeeIds)) {
               
                $shiftExist = $this->store->getFacade()::table('employeeShift')->whereIn('employeeId',$newEmployeeIds)->get();

                if ($shiftExist->isNotEmpty()) {
                    return $this->error(400, Lang::get('shiftAssignMessages.basic.ERR_SHIFT_EXIST'), NULL);
                }

                if (!isset($data['effectiveDate'])) {
                    return $this->error(400, Lang::get('shiftAssignMessages.basic.ERR_EFFECTIVE_DATE'), NULL);
                }
                
                $shiftData = [] ;
                foreach ($newEmployeeIds as $empId) {
                    $shiftAssignArray = [
                       'workshiftId' => $data['shiftId'],
                       'employeeId' => $empId,
                       'effectiveDate' => isset($data['effectiveDate']) ? $data['effectiveDate'] : ''
                    ];
                    $shiftData [] = $shiftAssignArray;
                }
                $shiftAssign =  $this->store->getFacade()::table('employeeShift')->insert($shiftData);
            }
            
            if (!empty($removeOldEmployeeIds)) {
              
                $shiftAssign =  $this->store->getFacade()::table('employeeShift')
                    ->where('workShiftId' ,$data['shiftId'])
                    ->whereIn('employeeId', $removeOldEmployeeIds)
                    ->delete();
            }

            if (!empty($newEmployeeIds) && !empty($data['effectiveDate'])) {
                // run attendance based on company timezone
                $timeZone = ($this->session->getCompany()->timeZone) ? $this->session->getCompany()->timeZone : 'UTC';
                $locationDateObject = Carbon::now($timeZone);
                $period = CarbonPeriod::create($data['effectiveDate'], $locationDateObject->format('Y-m-d'));

                $dates = [];
                foreach ($period as $dateObject) {
                    $dates[] = $dateObject->format('Y-m-d');
                }

                // run attendance process
                $tenantId = $this->session->getTenantId();
                $data = [
                    'tenantId' => $tenantId,
                    'employeeIds' => $newEmployeeIds,
                    'dates' => $dates
                ];

                // for backdated leave accrual
                dispatch(new BackDatedAttendanceProcess($data));
            }

            return $this->success(201, Lang::get('shiftAssignMessages.basic.SUCC_CREATE'), $shiftAssign);
           
        } catch (Exception $e) {
          
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('shiftAssignMessages.basic.ERR_CREATE'), null);
        }
    }

    /** 
     * Following function retrive shiftAssign by id.
     * 
     * @param $id email id
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "shiftAssign retrieved Successfully",
     *      $data => {id: 2, workShiftId: 21, employeeId: 2, effectiveDate: "2022-10-27"}
     * ]
     */
    public function getShiftAssign($id)
    {
        try {

            $shiftAssign = $this->store->getFacade()::table('employeeShift')
                ->leftJoin('employee', 'employee.id' ,"=",'employeeShift.employeeId')
                ->select(
                   'employeeShift.*',
                   DB::raw("CONCAT_WS(' ', employee.firstName, employee.middleName, employee.lastName) AS employeeName"),
                   'employee.id',
                   'employee.employeeNumber'
                )
                ->where('employeeShift.workShiftId', $id)
                ->get();

            return $this->success(200, Lang::get('shiftAssignMessages.basic.SUCC_SINGLE_RETRIVE'), $shiftAssign);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('shiftAssignMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }
    
    /**
     * get employees who is not assigned to any shifts
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Employees loaded successfully",
     *      $data => [{employeeName: "John Dave", id: 1},..]
     * ]
     */
    public function getShiftUnassignedEmployees($params) {
        try {
            $shiftEmployeesIds = $this->store->getFacade()::table('employeeShift')->pluck('employeeId')->toArray();
            $workPatternEmployeesIds = $this->store->getFacade()::table('employeeWorkPattern')->where('isActivePattern', true)->pluck('employeeId')->toArray();
            
            $allShiftAssignEmployees = array_merge($shiftEmployeesIds, $workPatternEmployeesIds);
            if (!empty($params['targetKeys']) && sizeof(json_decode($params['targetKeys'])) > 0) {
                $allShiftAssignEmployees = array_merge($allShiftAssignEmployees, json_decode($params['targetKeys']));
            }
            
            $allShiftAssignEmployees = array_values(array_unique($allShiftAssignEmployees));
   
            $employees =$this->store->getFacade()::table('employee')
                ->leftJoin('employeeJob', 'employeeJob.id' ,"=",'employee.currentJobsId')
                ->select(
                   DB::raw("CONCAT_WS(' ', employee.firstName, employee.middleName, employee.lastName) AS employeeName"),
                   'employee.id',
                   'employee.employeeNumber',
                )
                ->where('employee.isDelete', false)
                ->where('employee.isActive', true)
                ->whereNotIn('employee.id', $allShiftAssignEmployees);

            if (!empty($params['locationId'])) {
                $employees = $employees->where('employeeJob.locationId',$params['locationId']);
            }

            if (!empty($params['orgEntityId'])) {

                $relatedEntityIds = $this->getParentEntityRelatedChildNodes((int)$params['orgEntityId']);
                array_push($relatedEntityIds, (int)$params['orgEntityId']);

                

                $employees = $employees->whereIn('employeeJob.orgStructureEntityId',$relatedEntityIds);
            }
            $employees = $employees->get();
                
            return $this->success(200, Lang::get('shiftAssignMessages.basic.SUCC_EMPLOYEE_RETRIVE'), $employees);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('shiftAssignMessages.basic.ERR_EMPLOYEE_RETRIVE'), null);
        }
    }

    public function getParentEntityRelatedChildNodes($id)
    {

        $items = $this->store->getFacade()::table("orgEntity")->where('isDelete', false)->get();
        $kids = [];
        foreach ($items as $key => $item) {
            $item = (array) $item;
            if ($item['parentEntityId'] === $id) {
                $kids[] = $item['id'];
                array_push($kids, ...$this->getParentEntityRelatedChildNodes($item['id'], $items));
            }
        }

        return $kids;
    }
}

