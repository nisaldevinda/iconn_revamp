<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Library\Interfaces\ModelReaderInterface;
use App\Exceptions\Exception;
use App\Jobs\BackDatedAttendanceProcess;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Library\Util;
use App\Traits\JsonModelReader;
use Carbon\Carbon;
use stdClass;
use Illuminate\Support\Facades\DB;
use App\Library\FileStore;
use App\Library\Session;
use App\Traits\EmployeeHelper;
/**
 * Name: WorkScheduleService
 * Purpose: Performs tasks related to the WorkSchedule model.
 * Description: WorkSchedule Service class is called by the WorkScheduleController where the requests related
 * to WorkSchedule Model (basic operations and others). Table that is being modified is WorkSchedule.
 * Module Creator: Shobana
 */
class WorkScheduleService extends BaseService
{
    use JsonModelReader;
    use EmployeeHelper;

    private $store;

    private $workShiftModel;
    private $mployeeWorkPatternModel;
    private $fileStorage;
    private $session;
    public function __construct(Store $store,FileStore $fileStorage ,Session $session)
    {
        $this->store = $store;
        $this->workShiftModel = $this->getModel('workShifts', true);
        $this->employeeWorkPatternModel = $this->getModel('employeeWorkPattern',true);
        $this->fileStorage = $fileStorage;
        $this->session = $session;
    }
    

    /**
     * Following function creates a workShift.
     *
     * @param $workSchedule array containing the workSchedule data
     * @return int | String | array
     *
     * Usage:
     * $workShifts => ["name": "Voluntary"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "workSchedule created Successuflly",
     * $data => {"name": "Voluntary"}//$data has a similar set of values as the input
     *  */

    public function createAdhocWorkShift($workShifts)
     {
        try {

            $employeeIds = [];
            
            foreach( $workShifts['employeeId'] as $empId) {
                $employeeIds[] = $empId['id'];
                $checkShiftExists = DB::table('adhocWorkshifts')->where('date',$workShifts['date'])->where('employeeId',$empId['id'])->first();
                $workShiftId = isset($workShifts['shiftId']) ? $workShifts['shiftId'] : NULL;
                if (empty($checkShiftExists)) {
                    $adhocWorkShift = DB::table('adhocWorkshifts')
                       ->insertGetId([
                          'workshiftId' => $workShiftId, 
                          'date'       => $workShifts['date'],
                          'employeeId' => $empId['id']
                        ]);
                } else {
                    $adhocWorkShift = DB::table('adhocWorkshifts')->where('id',$checkShiftExists->id)->update(['workShiftId' =>   $workShiftId]);
                }
            }

            if (!empty($employeeIds)) {
                // run attendance process
                $tenantId = $this->session->getTenantId();
                $data = [
                    'tenantId' => $tenantId,
                    'employeeIds' => $employeeIds,
                    'dates' => [$workShifts['date']]
                ];
                // for backdated leave accrual
                dispatch(new BackDatedAttendanceProcess($data));
            }

            return $this->success(201, Lang::get('workScheduleMessages.basic.SUCC_CREATE'),  $adhocWorkShift);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workScheduleMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all WorkSchedules.
     *
     * @return int | String | array
     * Usage:
     * $request => [
     *  'start': '2022-03-14',
     *  'end': '2022-03-20'
     *  'currentPage': 1,
     *  'pageSize: 10
     *  ]
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "WorkSchedules loaded Successuflly",
     *      current: "1",
     *      pageSize: "10",
     *      total: 11,
     *      $data => [
     *        {
     *         "key":1,
     *         "id":1,
     *         "name":"John Dave",
     *         "mon":[{"name":"10 am to 7pm","date":"07-02-2022","type":"shift"}],
     *         "tue":[{"name":"pattern 2","date":"08-02-2022","type":"shift"}],
     *         "wed":[{"name":"pattern 2","date":"09-02-2022","type":"shift"}]
     *       },
     *       {
     *         "key":2,
     *         "id":2,
     *         "name":"John",
     *         "mon":[{"name":"10 am to 7pm","date":"07-02-2022","type":"shift"}],
     *         "tue":[{"name":"pattern 2","date":"08-02-2022","type":"shift"}],
     *         "wed":[{"name":"pattern 2","date":"09-02-2022","type":"shift"}],
     *         "thu":[{"name":"pattern 2","date":"09-02-2022","type":"shift"}]
     *       },
     *      ]
     * ]
     */
    public function getAllWorkSchedules($request)
    {
       
        try {
            $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();
            
            if (isset($request['employeeIds'])) {
                $employeeId = explode(',',$request['employeeIds']);
                if (!array_intersect($employeeId ,$permittedEmployeeIds)) {
                    return $this->error(403, Lang::get('workScheduleMessages.basic.ERR_PERMISSION'), NULL);
                }
                $employeesList = $this->store->getFacade()::table('employee')->whereIn('id',$employeeId )->where('isDelete', '=', false)->where('isActive', '=', true);
            } else {
                $employeesList = $this->store->getFacade()::table('employee')->whereIn('id', $permittedEmployeeIds)->where('isDelete', '=', false)->where('isActive', '=', true);
            }
           
            $total = null;
            if (isset($request['search']) && !empty($request['search'])) {
                $employeesList = $employeesList->where('firstName', 'like', '%' . $request['search'] . '%')->orWhere('lastName', 'like', '%' . $request['search'] . '%');
            }

            if (isset($request['currentPage']) && isset($request['pageSize'])) {
                 
                $page = (int) ($request['currentPage'] > 0 ? (int) $request['currentPage'] - 1: 0) * $request['pageSize'];
                $total =  $employeesList->count();
                $employeesList =  $employeesList->limit($request['pageSize'])->offset($page);
            }
            $results =  $employeesList->get(['id','firstName','middleName','lastName','workEmail','profilePicture']);
            
            $workSchedules = [];
            foreach ($results  as $employees) {
               $starting_date = Carbon::parse($request['start']);
               $starting_date->setTime(0, 0, 0);
               $ending_date = Carbon::parse($request['end']);
             $data=$this->generateWorkScheduleData($request['start'],$request['end'],$employees,false);
                array_push( $workSchedules, $data);
            }
        
                $workSchedules = [
                    "current" => $request["currentPage"],
                    "pageSize" => $request["pageSize"],
                    "total" => $total,
                    "data" => $workSchedules
                ];
            
            return $this->success(200, Lang::get('workScheduleMessages.basic.SUCC_ALL_RETRIVE'), $workSchedules);
       
        
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workScheduleMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
    /** 
     * Following function retrives a assigned workPatterns  of an employee for a given empId.
     * 
     * @param $empId employee Id
     * @return int | String | array
     * 
     * Usage:
     * $empId => 1
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Work pattern  loaded successfully",
     *      $data => [{"firstName":"John","lastName":"Dave","effectiveDate":"2022-02-10","name":"feb pattern","workpatternId":4,"id":18}]
     * ]
     */
    public function getEmployeeWorkPattern($employeeId) {
        try {
            
           $empId = $employeeId['empId'];
           
           $employeePattern =  $this->store->getFacade()::table('employeeWorkPattern')
                ->select(
                  'effectiveDate',
                  'name',
                  'workpatternId',
                  'employeeWorkPattern.id',
                  DB::raw("CONCAT_WS(' ', firstName, middleName, lastName) AS employeeName")
                )
                ->leftJoin('employee','employee.id',"=",'employeeWorkPattern.employeeId')
                ->leftJoin('workPattern','workPattern.id','=','employeeWorkPattern.workPatternId')   
                ->where('employeeId',$empId)
                ->orderBy('employeeWorkPattern.updatedAt','DESC')
                ->get()
                ->all();
            
            // $employeeDefaultPattern = $this->store->getFacade()::table('workPatternLocation')
            //     ->select(
            //         'effectiveDate',
            //         'name',
            //         'workpatternId',
            //         'employeeJob.locationId',
            //         DB::raw("CONCAT_WS(' ', firstName, middleName, lastName) AS employeeName")
            //     )
            //     ->leftJoin('employeeJob','employeeJob.locationId','=','workPatternLocation.locationId')
            //     ->leftJoin('employee','employee.id',"=",'employeeJob.employeeId')
            //     ->leftJoin('workPattern','workPattern.id','=','workPatternLocation.workPatternId')   
            //     ->where('employeeJob.employeeId',$empId)
            //     ->get()
            //     ->all();
            $employeeDefaultPattern = [];
           
            $empPattern = array_merge($employeePattern, $employeeDefaultPattern);
            $employyeWorkPatternList = collect($empPattern)->sortByDesc('id')->unique('effectiveDate')->values()->sortByDesc('effectiveDate');
            
            $count =0 ;
            $record =[];
            foreach ($employyeWorkPatternList as $item) {
                if ($item->effectiveDate <= Carbon::now()->format('Y-m-d'))  {
                    $count = $count+1;
                    if ($count == 1 ) {
                        $item->currentRecord = 1;
                    }
                }
                array_push($record,$item);
            }

            $employeeAssignedPatterns =collect($record)->sortBy('effectiveDate')->values();
            $assignedPatterns = [];
            foreach ( $employeeAssignedPatterns as $key => $pattern) {
                if (isset($pattern->locationId) && $key != 0 ) {
                    $pattern->removeRecord = 1;
                } else {
                    $pattern->removeRecord = 0;
                }
                
                if($pattern->removeRecord !=1) {
                    array_push($assignedPatterns,$pattern);
                }
            }
            
        return $this->success(200, Lang::get('workScheduleMessages.basic.SUCC_SINGLE_RETRIVE'),  $assignedPatterns);
       
        
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workScheduleMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }

    /**
     * updating a new work pattern for an employee
     * @param $workPattern array containing the WorkPattern  data
     * @return int | String | array
     * 
     * Usage:
     * $workPattern  => {"firstName":"John Dave","patternId":5,"employeeId":1,"currentPattern":{"workPatternId":5,"id":18}}
     *   "employeeId":1,
     *   "firstName":"John Dave",
     *   "patternId":5
     *   "currentPattern":{"workPatternId":5,"id":18}.
     *   "pattern":[{"effectiveDate":"2022-02-22T05:21:12.219Z","patternId":4}]
     * }
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "WorkPattern  updated Successuflly",
     * $data => {id: 23, employeeId: 4, workPatternId: 1, effectiveDate: "2022-02-08" }
     *  
     */
    public function createEmployeeWorkPattern($workPattern ) {
        try {
           if (isset($workPattern['pattern']) && !empty($workPattern['pattern'])) {
                $pattern = $workPattern['pattern'];
                foreach ($pattern as $value) {
                  $employeePattern = [];
                  $employeePattern['employeeId'] = $workPattern['employeeId'];
                  $employeePattern['effectiveDate'] = Carbon::parse($value['effectiveDate'])->format('Y-m-d');
                  $employeePattern['workPatternId'] = $value['patternId'];
                  $employeePatternRecord = $this->store->insert($this->employeeWorkPatternModel,$employeePattern , true);
                }
           }

           if (isset($workPattern['currentPattern']) && !empty($workPattern['currentPattern'])) {
               $currentPattern = $workPattern['currentPattern'];
               $update = [];
               $id = $currentPattern['id'];
                if (isset($currentPattern['effectiveDate'])) {
                  $update['effectiveDate'] = $currentPattern['effectiveDate'];
                }
                if (isset($currentPattern['workPatternId'])) {
                  $update['workPatternId'] = $currentPattern['workPatternId'];
                }
                
                $employeePatternRecord = $this->store->updateById($this->employeeWorkPatternModel, $id,  $update);
            }

            return $this->success(200, Lang::get('workScheduleMessages.basic.SUCC_UPDATE_PATTERN'), $employeePatternRecord);

        } catch (Exception $e) {
           Log::error($e->getMessage());
          return $this->error($e->getCode(), Lang::get('workScheduleMessages.basic.ERR_UPDATE_PATTERN'), null);
        }
    }

    /**
     * get the list of workShifts
     *  Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "All Work shift retrieved Successfully.",
     *      $data => [{"name": "8 to 5", "noOfDay" : 1}, ...]
     * ] 
    */
    public function  getWorkShifts() {
        try {
            $workShifts = $this->store->getFacade()::table('workShifts')
                ->get();
            return $this->success(200, Lang::get('workScheduleMessages.basic.SUCC_ALL_RETRIVE_SHIFTS'), $workShifts);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workScheduleMessages.basic.ERR_ALL_RETRIVE_SHIFTS'), null);
        }
    }

    /**
     * get the list of workShifts
     *  Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "All Work shift retrieved Successfully.",
     *      $data => [{"name": "8 to 5", "noOfDay" : 1}, ...]
     * ] 
    */
    public function  getWorkShiftsForShiftChange() {
        try {
            $workShifts = $this->store->getFacade()::table('workShifts')
                ->get();
            return $this->success(200, Lang::get('workScheduleMessages.basic.SUCC_ALL_RETRIVE_SHIFTS'), $workShifts);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workScheduleMessages.basic.ERR_ALL_RETRIVE_SHIFTS'), null);
        }
    }

    /** 
     * Following function retrive Work Shift by id.
     * 
     * @param $id  id
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Work shift retrieved Successfully",
     *      $data => {"name": "8 to 5", "noOfDay" : 1}
     * ]
     */
    public function  getWorkShiftById($id) {
        try {
            $workShifts = $this->store->getFacade()::table('workShifts')
                ->leftJoin('workShiftDayType', 'workShiftDayType.workShiftId' ,'=' ,'workShifts.id')   
                ->where('workShifts.id',$id)
                ->first();
            return $this->success(200, Lang::get('workScheduleMessages.basic.SUCC_SINGLE_RETRIVE_SHIFT'), $workShifts);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workScheduleMessages.basic.ERR_SINGLE_RETRIVE_SHIFT'), null);
        }
    }

     /** 
     * Following function retrive my workschedule by month
     * 
     * @param $id  id
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Work shift retrieved Successfully",
     *      $data => {"name": "8 to 5", "noOfDay" : 1}
     * ]
     */
    public function  getMyWorkSchedule($request,$employeeId) {
        try {
            $employees=new stdClass();
            $employees->id=$employeeId;
            $empPattern= $this->generateWorkScheduleData($request['start'],$request['end'],$employees,true);
            return $this->success(200, Lang::get('workScheduleMessages.basic.SUCC_SINGLE_RETRIVE_SHIFT'), $empPattern);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workScheduleMessages.basic.ERR_SINGLE_RETRIVE_SHIFT'), null);
        }
    }

    /** 
     * Following function retrive employee workschedule by month
     * 
     * @param $id  id
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Work shift retrieved Successfully",
     *      $data => {"name": "8 to 5", "noOfDay" : 1}
     * ]
     */
    public function  getEmployeeWorkSchedule($request) {
        try {
            $employees=new stdClass();
            $employees->id=$request['id'];
            $empPattern= $this->generateWorkScheduleData($request['start'],$request['end'],$employees,true);
            return $this->success(200, Lang::get('workScheduleMessages.basic.SUCC_SINGLE_RETRIVE_SHIFT'), $empPattern);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workScheduleMessages.basic.ERR_SINGLE_RETRIVE_SHIFT'), null);
        }
    }


    /** 
     * Following function retrive employee work shift by date
     * 
     * @param $request  params that need to retirve workshifts
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Work shift retrieved Successfully",
     *      $data => {"name": "8 to 5", "noOfDay" : 1}
     * ]
     */
    public function  getEmployeeWorkShiftByDate($request) {
        try {
            $employeeId = $this->session->getEmployee()->id;
            $date = $request['date'];
            $dateObj = Carbon::parse($date);

            $shift = $this->getEmployeeWorkShift($employeeId, $dateObj);
           
            return $this->success(200, Lang::get('workScheduleMessages.basic.SUCC_SINGLE_RETRIVE_SHIFT'), $shift);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workScheduleMessages.basic.ERR_SINGLE_RETRIVE_SHIFT'), null);
        }
    }

    /** 
     * Following function generate data for workschedule components
     * 
     */
    public function generateWorkScheduleData( $start,$end,$employees,$monthly) {
        
        $starting_date = Carbon::parse($start);
        $starting_date->setTime(0, 0, 0);
        $ending_date = Carbon::parse($end);
        $ending_date->setTime(23, 59, 59);
        $interval = \DateInterval::createFromDateString('1 day');
        $timePeriod = new \DatePeriod($starting_date, $interval, $ending_date);
                 
        $field =''; 
                     
      
        $empPattern = new stdClass();
        if (!$monthly) {
            if ($employees->profilePicture != 0) {
               $field = $this->fileStorage->getBase64EncodedObject($employees->profilePicture);
            }
            $empPattern->key = $employees->id ;
            $empPattern->id = $employees->id ;
            $empPattern->name = $employees->firstName.' '. $employees->middleName.' '. $employees->lastName ;
            $empPattern->workEmail = $employees->workEmail ;
            $empPattern->profilePic = !empty($field) ? $field->data : '';
        }


        foreach ($timePeriod as $period) {
            $dayValue = [];
            $workPattern = collect();
            $employeePattern =  $this->store->getFacade()::table('employeeWorkPattern')
                ->where('effectiveDate','<=',Carbon::parse($period)->format('Y-m-d'))
                ->where('employeeId',$employees->id)
                ->orderBy('effectiveDate','DESC')
                ->orderBy('id','DESC')
                ->first();

            $employeeCalendar =  $this->store->getFacade()::table('employeeJob')
                ->where('effectiveDate','<=',Carbon::parse($period)->format('Y-m-d'))
                ->where('employeeId',$employees->id)
                ->orderBy('id','DESC')->first();

            if (!empty($employeeCalendar)) {
                
            // get day name for given date
            $dayName = $period->format('l');
            $calendarDayTypeId = $this->getCalendarDayTypeId($employeeCalendar->calendarId, $dayName);
           
            if (!empty($employeePattern)) {

                $workPattern = $this->store->getFacade()::table('workPattern')
                    ->select('workPattern.name AS patternName','workShifts.name','workShiftDayType.noOfDay','workShiftDayType.startTime','workShiftDayType.endTime','workShiftDayType.workHours','workShiftDayType.breakTime','workShiftDayType.hasMidnightCrossOver','workShifts.color' )
                    ->leftJoin('workPatternWeek','workPatternWeek.workPatternId','=','workPattern.id')
                    ->leftJoin('workPatternWeekDay','workPatternWeekDay.workPatternWeekId','=','workPatternWeek.id')
                    ->leftJoin('dayOfWeek','dayOfWeek.id','=','workPatternWeekDay.dayTypeId')
                    ->leftJoin('workShifts','workShifts.id','=','workPatternWeekDay.workShiftId')
                    ->leftJoin('workShiftDayType', 'workShiftDayType.workShiftId' ,'=' ,'workShifts.id')   
                    ->where('workShiftDayType.dayTypeId', $calendarDayTypeId)
                    ->where('dayOfWeek.dayName','=',strtolower($period->format('l')))
                    ->where('workPattern.id','=',$employeePattern->workPatternId)
                    ->get();
            } else {
                // $empLocation = $this->store->getFacade()::table('employeeJob')
                //     ->where('effectiveDate','<=',Carbon::parse($period)->format('Y-m-d'))
                //     ->where('employeeId',$employees->id)
                //     ->orderBy('id','DESC')->first();

                //     if (!empty($empLocation)) {    

                //         $workPattern = $this->store->getFacade()::table('workPatternLocation')
                //             ->select('workPattern.name AS patternName','workShifts.name','workShiftDayType.noOfDay','workShiftDayType.startTime','workShiftDayType.endTime','workShiftDayType.workHours','workShiftDayType.breakTime','workShiftDayType.hasMidnightCrossOver','workShifts.color')
                //             ->leftJoin('workPattern','workPattern.id','=','workPatternLocation.workPatternId')    
                //             ->leftJoin('workPatternWeek','workPatternWeek.workPatternId','=','workPattern.id')
                //             ->leftJoin('workPatternWeekDay','workPatternWeekDay.workPatternWeekId','=','workPatternWeek.id')
                //             ->leftJoin('dayOfWeek','dayOfWeek.id','=','workPatternWeekDay.dayTypeId')
                //             ->leftJoin('workShifts','workShifts.id','=','workPatternWeekDay.workShiftId')
                //             ->leftJoin('workShiftDayType', 'workShiftDayType.workShiftId' ,'=' ,'workShifts.id')   
                //             ->where('workShiftDayType.dayTypeId', $calendarDayTypeId)
                //             ->where('dayOfWeek.dayName','=',strtolower($period->format('l')))
                //             ->where('locationId',$empLocation->locationId)
                //             ->get();
                //     }
            }
        
            $shiftDataExist = $this->store->getFacade()::table('workShifts')
                ->leftJoin('workShiftDayType', 'workShiftDayType.workShiftId' ,'=' ,'workShifts.id')   
                ->leftJoin('adhocWorkshifts','adhocWorkshifts.workShiftId','=','workShifts.id')
                ->where('adhocWorkshifts.employeeId',$employees->id)
                ->where('adhocWorkshifts.date',Carbon::parse($period)->format('Y-m-d'))
                ->where('workShiftDayType.dayTypeId', $calendarDayTypeId)
                ->first();

            $employeeShiftExist =  $this->store->getFacade()::table('employeeShift')
                ->leftJoin('workShifts','workShifts.id','=','employeeShift.workShiftId')
                ->leftJoin('workShiftDayType', 'workShiftDayType.workShiftId' ,'=' ,'workShifts.id')  
                ->where('workShiftDayType.dayTypeId', $calendarDayTypeId) 
                ->where('employeeShift.employeeId',$employees->id)
                ->where('employeeShift.effectiveDate','<=',Carbon::parse($period)->format('Y-m-d'))
                ->first();  

            $leaveDetailExist = $this->store->getFacade()::table('leaveRequestDetail')
                ->leftJoin('leaveRequest','leaveRequest.id','=','leaveRequestDetail.leaveRequestId')
                ->leftJoin('leaveType','leaveType.id','=', 'leaveRequest.leaveTypeId')
                ->where('leaveRequest.employeeId',$employees->id)
                ->where('leaveRequestDetail.leaveDate',Carbon::parse($period)->format('Y-m-d'))
                ->where('leaveRequestDetail.status','APPROVED')
                ->first();

           
            $holidayExist = null;

            if (!empty($employeeCalendar->calendarId)) {

                $holidayExist = $this->store->getFacade()::table('workCalendarSpecialDays')
                    ->join('workCalendarDayType','workCalendarDayType.id',"=",'workCalendarSpecialDays.workCalendarDayTypeId')
                    ->where('workCalendarSpecialDays.date' ,Carbon::parse($period)->format('Y-m-d'))
                    ->where('workCalendarSpecialDays.calendarId', $employeeCalendar->calendarId)
                    ->where('workCalendarSpecialDays.workCalendarDayTypeId',3)
                    ->first();
            } 


            if (!empty($shiftDataExist)) {
                $shift = new stdClass();
                $shift->name = $shiftDataExist->name;
                $shift->date = Carbon::parse($period)->format('d-m-Y');
                $shift->type = 'shift';
                $shift->days = $shiftDataExist->noOfDay;
                $shift->startTime = $shiftDataExist->startTime;
                $shift->endTime = $shiftDataExist->endTime;
                $shift->workHours = $shiftDataExist->workHours;
                $shift->breakTime = $shiftDataExist->breakTime;
                $shift->hasMidnightCrossOver = $shiftDataExist->hasMidnightCrossOver;
                $shift->empName = !$monthly ? $empPattern->name : null;
                $shift->empId  = !$monthly ? $empPattern->id : null;
                $shift->color = $shiftDataExist->color;   
                array_push($dayValue, $shift);
            } else if (!empty($employeeShiftExist)) {
                $employeeShift = new stdClass();
                $employeeShift->name = $employeeShiftExist->name;
                $employeeShift->date = Carbon::parse($period)->format('d-m-Y');
                $employeeShift->type = 'shift';
                $employeeShift->days = $employeeShiftExist->noOfDay;
                $employeeShift->startTime = $employeeShiftExist->startTime;
                $employeeShift->endTime = $employeeShiftExist->endTime;
                $employeeShift->workHours = $employeeShiftExist->workHours;
                $employeeShift->breakTime = $employeeShiftExist->breakTime;
                $employeeShift->hasMidnightCrossOver = $employeeShiftExist->hasMidnightCrossOver;
                $employeeShift->empName = !$monthly ? $empPattern->name : null;
                $employeeShift->empId  = !$monthly ? $empPattern->id : null;
                $employeeShift->color = $employeeShiftExist->color;   
                array_push($dayValue, $employeeShift);
            } else if (!empty($leaveDetailExist)) {
                $leave = new stdClass();
                $leave->name = $leaveDetailExist->name;
                $leave->date = Carbon::parse($period)->format('d-m-Y');
                $leave->type = 'leave';
                              
                array_push($dayValue,  $leave);
            } else if (!empty($holidayExist)) {
                $holiday = new stdClass();
                $holiday->name = $holidayExist->name;
                $holiday->date = Carbon::parse($period)->format('d-m-Y');
                $holiday->type = 'holiday';
                $holiday->color = $holidayExist->typeColor;
            
                array_push($dayValue, $holiday);
            }  else {
                if ($workPattern->isNotEmpty()) {
                    $pattern = new stdClass();
                    $pattern->name =  $workPattern[0]->name ;
                    $pattern->patternName = $workPattern[0]->patternName;
                    $pattern->date = Carbon::parse($period)->format('d-m-Y');
                    $pattern->type = 'pattern';
                    $pattern->days =$workPattern[0]->noOfDay;
                    $pattern->startTime = $workPattern[0]->startTime;
                    $pattern->endTime = $workPattern[0]->endTime;
                    $pattern->workHours = $workPattern[0]->workHours;
                    $pattern->breakTime = $workPattern[0]->breakTime;
                    $pattern->hasMidnightCrossOver = !$monthly ? $workPattern[0]->hasMidnightCrossOver : null;
                    $pattern->empName = !$monthly ? $empPattern->name : null;
                    $pattern->empId  = !$monthly ? $empPattern->id : null;
                    $pattern->color = $workPattern[0]->color;
                    array_push($dayValue,$pattern);
                }
            }

            if (!$monthly) {
                $dayName = $period->format('l');
                $dayName = strtolower(substr("$dayName", 0, 3));
                $empPattern->$dayName = $dayValue;
            }
                $dayName = $period->format('d-m-Y');
                $empPattern->$dayName = $dayValue;
                      
            }            
        }
                       
        return $empPattern;
    }
    
}