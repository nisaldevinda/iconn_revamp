<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Library\RelationshipType;
use App\Traits\JsonModelReader;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Library\Session;
use App\Jobs\BackDatedAttendanceProcess;

/**
 * Name:  WorkShiftService
 * Purpose: Performs tasks related to the  WorkShift model.
 * Description:  WorkShiftService class is called by the   WorkShiftController where the requests related
 * to WorkShift Model (basic operations and others). Table that is being modified is WorkShift.
 * Module Creator: Shobana
 */
class WorkShiftService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $workShiftModel;
    private $session;
    public function __construct(Store $store ,Session $session)
    {
        $this->store = $store;
        $this->workShiftModel = $this->getModel('workShifts', true);
        $this->workShiftDayTypeModel = $this->getModel('workShiftDayType', true);
        $this->session = $session;
    }

    /**
     * Following function create a Work Shift.
     * 
     * @param $data array of workShift data
     * 
     * Usage:
     * $data => ["name": "pattern"]
     * 
     * Sample output:
     * $statusCode => 201,
     * $message => "workShift created Successuflly",
     * $data => {"name": "Pattern A", "description": "this is Pattern A"}
     *  */

    public function createWorkShift($data)
    {
        try {
            $validationResponse = ModelValidator::validate($this->workShiftModel, $data, false);
           
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('workShiftMessages.basic.ERR_CREATE'), $validationResponse);
            }

            DB::beginTransaction();
            $workshiftArray = [
                'name' => isset($data['name']) ? $data['name'] : NULL,
                'code' => isset($data['code']) ? $data['code'] : NULL,
                'shiftType' => isset($data['shiftType']) ? $data['shiftType'] : NULL,
                'color' => isset($data['color']) ? $data['color'] : NULL,
                'isActive' => isset($data['isActive']) ? $data['isActive'] : 0,
            ];
            $workShift = $this->store->insert($this->workShiftModel,  $workshiftArray, true);
            $dayTypeId = isset($data['dayType']) ? $data['dayType'] : NULL;
            $payConfigType = isset($data['payConfigType']) ? $data['payConfigType'] : NULL;

            $isEnableInOT = false;

            if ($data['shiftType'] == 'FLEXI') {
                $isEnableInOT = true;
            } else {
                if (isset($data['inOvertime'])) {
                    $isEnableInOT = $data['inOvertime'];
                }
            }

            $numOfDay = NULL;
            if (isset($data['noOfDay'])) {

                switch ($data['noOfDay']) {
                    case '1':
                        $numOfDay = 1.0;
                        break;
                    case '0.5':
                        $numOfDay = 0.5;
                        break;
                    case '0':
                        $numOfDay = 0;
                        break;
                    default:
                        $numOfDay = null;
                        break;
                }
            }

            $breakTime = NULL;

            if (isset($data['breakTime'])) {
                $breakTime = ($data['breakTime'] == "0") ? 0 : $data['breakTime'];
            }

            $workShiftDayType = [
                'dayTypeId' =>isset($data['dayType']) ? $data['dayType'] : NULL,
                'workShiftId' => isset($workShift['id']) ? $workShift['id'] : NULL,
                'noOfDay' => $numOfDay,
                'startTime' => isset($data['startTime']) ? $data['startTime'] : NULL ,
                'endTime' =>  isset($data['endTime']) ? $data['endTime'] : NULL,
                'breakTime' => $breakTime,
                'workHours' => isset($data['workHours']) ? $data['workHours'] : NULL ,
                'halfDayLength' => isset($data['halfDayLength']) ? $data['halfDayLength'] : NULL ,
                'gracePeriod' => isset($data['gracePeriod']) ? $data['gracePeriod'] : NULL,
                'minimumOT' => isset($data['minimumOT']) ? $data['minimumOT'] : NULL,
                'roundOffMethod' => isset($data['roundOffMethod']) ? $data['roundOffMethod'] :NULL,
                'roundOffToNearest' => isset($data['roundOffToNearest']) ? $data['roundOffToNearest']  : NULL,
                'isOTEnabled' => isset($data['isOTEnabled']) ? $data['isOTEnabled'] : 0 ,
                'isBehaveAsNonWorkingDay' => isset($data['isBehaveAsNonWorkingDay']) ? $data['isBehaveAsNonWorkingDay'] : 0 ,
                'inOvertime' => $isEnableInOT,
                'outOvertime' => isset($data['outOvertime']) ? $data['outOvertime'] : 0 ,
                'deductLateFromOvertime' =>isset($data['deductLateFromOvertime']) ? $data['deductLateFromOvertime'] : 0,
                'hasMidnightCrossOver' => isset($data['hasMidnightCrossOver']) ? $data['hasMidnightCrossOver'] : 0,
            ];
            
            $workShiftDayType = $this->store->insert($this->workShiftDayTypeModel,  $workShiftDayType, true);
           
            if (!empty($data['payConfigData'])) {
                $workShiftPayConfig = $this->workShiftPayConfig($workShift['id'],$data['payConfigData'], $dayTypeId, $payConfigType);
            }
           
            DB::commit();
            return $this->success(201, Lang::get('workShiftMessages.basic.SUCC_CREATE'), $workShift);
           
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workShiftMessages.basic.ERR_CREATE'), null);
        }
    }

    /** 
     * Following function retrive workShift by id.
     * 
     * @param $id email id
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "workShift retrieved Successfully",
     *      $data => {breakTime: "40",code: "SHIFT_00Z",deductLateFromOvertime: false,endTime: "12:00",
     *      gracePeriod: "10",halfDayLength: "",hasMidnightCrossOver: false,inOvertime: true
     *      isActive: false,isOTEnabled: true,minimumOT: "30",name: "SHIFT _Z",noOfDay: "0.5 Day",outOvertime: true
     *      roundOffMethod: "NO_ROUNDING",shiftType: "GENERAL",startTime: "06:00",workHours: "320"}
     * ]
     */
    public function getWorkShift($id)
    {
        try {
            $workShift =[];
            $workShift =$this->store->getFacade()::table('workShifts')
                ->leftJoin('workShiftDayType', 'workShiftDayType.workShiftId' ,"=",'workShifts.id')
                ->where('workShifts.id', $id)
                ->orderBy('workShiftDayType.dayTypeId','asc')
                ->get();
            if (is_null($workShift)) {
                return $this->error(404, Lang::get('workShiftMessages.basic.ERR_NONEXISTENT'), $workShift);
            }


            //if Workshift has configurations for working day day type this return working day related configurations other wise its return the intially created daytype workshift configurations
            $workingDayTypeData = null;
            //check whether alread has configurations for working day type
            foreach ($workShift as $key => $dayTypeConfigData) {
                if ($dayTypeConfigData->dayTypeId == 1) {
                    $workingDayTypeData = $dayTypeConfigData;
                    break;
                }
            }

            if (!is_null($workingDayTypeData)) {
                $workShift = $workingDayTypeData;  
            } else {
                $workShift = $workShift[0];
            }

            $dayTypeId = $workShift->dayTypeId;    

            $workShift->payData = $this->getPayConfiguration($id,$dayTypeId);
            $workShift->breakTime = (!is_null($workShift->breakTime)) ? $workShift->breakTime : 0;

            //get payConfigData
            $payConfigData =$this->store->getFacade()::table('workShiftPayConfiguration')
                ->where('workShiftPayConfiguration.workShiftId', $id)
                ->where('workShiftPayConfiguration.workCalendarDayTypeId', $dayTypeId)
                ->first();
            if (!is_null($payConfigData)) {
                $workShift->payConfigType = $payConfigData->payConfigType;
            }

            return $this->success(200, Lang::get('workShiftMessages.basic.SUCC_SINGLE_RETRIVE'), $workShift);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workShiftMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }
    
   
    /** 
     * Following function retrive all workShifts.
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "All workShift retrieved Successfully.",
     *       $data => {breakTime: "40",code: "SHIFT_00Z",deductLateFromOvertime: false,endTime: "12:00",
     *       gracePeriod: "10",halfDayLength: "",hasMidnightCrossOver: false,inOvertime: true
     *      isActive: false,isOTEnabled: true,minimumOT: "30",name: "SHIFT _Z",noOfDay: "0.5 Day",outOvertime: true
     *      roundOffMethod: "NO_ROUNDING",shiftType: "GENERAL",startTime: "06:00",workHours: "320"}]
     * ] 
     */
    public function listworkShifts($permittedFields, $options)
    { 
        try {
            
            $filteredWorkShift = $this->store->getAll(
                $this->workShiftModel,
                $permittedFields,
                $options,
                [],
                []
            );
           
            return $this->success(200, Lang::get('workShiftMessages.basic.SUCC_ALL_RETRIVE'),  $filteredWorkShift);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workShiftMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    
   
    /**
     * Following function updates workShift.
     * 
     * @param $id workShift id
     * @param $data array containing workShift data
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "workShift updated successfully.",
     *      $data => {breakTime: "40",code: "SHIFT_00Z",deductLateFromOvertime: false,endTime: "12:00",
     *      gracePeriod: "10",halfDayLength: "",hasMidnightCrossOver: false,inOvertime: true
     *      isActive: false,isOTEnabled: true,minimumOT: "30",name: "SHIFT _Z",noOfDay: "0.5 Day",outOvertime: true
     *      roundOffMethod: "NO_ROUNDING",shiftType: "GENERAL",startTime: "06:00",workHours: "320"}
     * 
     */

    public function updateworkShift($id, $data)
    {
        try {    
            DB::beginTransaction();  
            $data['id'] = $id;   
            $validationResponse = ModelValidator::validate($this->workShiftModel, $data, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('workShiftMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $workshiftArray = [
                'name' => isset($data['name']) ? $data['name'] : NULL,
                'code' => isset($data['code']) ? $data['code'] : NULL,
                'shiftType' => isset($data['shiftType']) ? $data['shiftType'] : NULL,
                'color' => isset($data['color']) ? $data['color'] : NULL,
                'isActive' => isset($data['isActive']) ? $data['isActive'] : 0,
            ];
            $workShift = $this->store->getFacade()::table('workShifts')->where('id', $id)->update($workshiftArray);
            $dayTypeId = isset($data['dayType']) ? $data['dayType'] : NULL;
            $payConfigType = isset($data['payConfigType']) ? $data['payConfigType'] : NULL;
            $isEnableInOT = false;

            if ($data['shiftType'] == 'FLEXI') {
                $isEnableInOT = true;
            } else {
                if (isset($data['inOvertime'])) {
                    $isEnableInOT = $data['inOvertime'];
                }
            }

            $workShiftDayType = [
                'dayTypeId' =>isset($data['dayType']) ? $data['dayType'] : NULL,
                'workShiftId' => $id,
                'noOfDay' => isset($data['noOfDay']) ? $data['noOfDay'] : NULL,
                'startTime' => isset($data['startTime']) ? $data['startTime'] : NULL ,
                'endTime' =>  isset($data['endTime']) ? $data['endTime'] : NULL,
                'breakTime' => isset($data['breakTime']) ? $data['breakTime'] : NULL,
                'workHours' => isset($data['workHours']) ? $data['workHours'] : NULL ,
                'halfDayLength' => isset($data['halfDayLength']) ? $data['halfDayLength'] : NULL ,
                'gracePeriod' => isset($data['gracePeriod']) ? $data['gracePeriod'] : NULL,
                'minimumOT' => isset($data['minimumOT']) ? $data['minimumOT'] : NULL,
                'roundOffMethod' => isset($data['roundOffMethod']) ? $data['roundOffMethod'] :NULL,
                'roundOffToNearest' => isset($data['roundOffToNearest']) ? $data['roundOffToNearest']  : NULL,
                'isOTEnabled' => isset($data['isOTEnabled']) ? $data['isOTEnabled'] : 0 ,
                'isBehaveAsNonWorkingDay' => isset($data['isBehaveAsNonWorkingDay']) ? $data['isBehaveAsNonWorkingDay'] : 0 ,
                'inOvertime' => $isEnableInOT,
                'outOvertime' => isset($data['outOvertime']) ? $data['outOvertime'] : 0 ,
                'deductLateFromOvertime' =>isset($data['deductLateFromOvertime']) ? $data['deductLateFromOvertime'] : 0,
                'hasMidnightCrossOver' => isset($data['hasMidnightCrossOver']) ? $data['hasMidnightCrossOver'] : 0,
            ];
            
            $checkDayTypeExist =  $this->store->getFacade()::table('workShifts')
               ->leftJoin('workShiftDayType', 'workShiftDayType.workShiftId' ,"=",'workShifts.id')
               ->where('workShiftDayType.workShiftId', $id)
               ->where('workShiftDayType.dayTypeId',$data['dayType'])
               ->first();

            if (empty($checkDayTypeExist)) {
                $workShiftDayType = $this->store->insert($this->workShiftDayTypeModel,  $workShiftDayType, true);
            } else {
                $workShiftDayType = $this->store->getFacade()::table('workShiftDayType')->where('id',$checkDayTypeExist->id)->update($workShiftDayType);
            }
            
            if (!empty($data['payConfigData'])) {
               
                $workShiftPayConfig = $this->workShiftPayConfig($id,$data['payConfigData'], $dayTypeId, $payConfigType);
            } else {
                //check already has pay config
                $oldDayTypeConfigData = $this->store->getFacade()::table('workShiftPayConfiguration')
                        ->where('workShiftId', $id)
                        ->where('workCalendarDayTypeId', $dayTypeId)
                        ->first();

                if (!is_null($oldDayTypeConfigData)) {

                    //delete related all pay config thresholds
                    $affectedThresholdRows = DB::table('workShiftPayConfigurationThreshold')->where('workShiftPayConfigurationId', $oldDayTypeConfigData->id)->delete();

                    //delete related workshift payconfig
                    $affectedConfigRows = DB::table('workShiftPayConfiguration')->where('id', $oldDayTypeConfigData->id)->delete();
                }
            }
           
            DB::commit();
            return $this->success(200, Lang::get('workShiftMessages.basic.SUCC_UPDATE'), $workShiftDayType);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workShiftMessages.basic.ERR_UPDATE'), null);
        }
    }

    /** 
     * Delete workShift by id.
     * 
     * @param $id email id
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => " workShift deleted Successfully",
     *      $data => {id: 1}
     * ]
     */
    public function deleteworkShift($id)
    {
        try {
            $dbTemplate = $this->store->getFacade()::table('workShifts')->where('id', $id)->first();
           
            if (is_null($dbTemplate)) {
                return $this->error(404, Lang::get('workShiftMessages.basic.ERR_DELETE'), null);
            }

            //check is there any attendance summary record relate to workshift id
            $relatedSummaryRecords = $this->store->getFacade()::table('attendance_summary')
                ->where('shiftId', $id)
                ->first();

            if (!empty($relatedSummaryRecords)) {
                return $this->error(502, Lang::get('workShiftMessages.basic.ERR_NOTALLOWED'), null);
            }

            //check whether any adhoc shift record related to this employee
            $relatedAdhocShiftRecords = $this->store->getFacade()::table('adhocWorkshifts')
                ->where('workShiftId', $id)
                ->first();

            if (!empty($relatedAdhocShiftRecords)) {
                return $this->error(502, Lang::get('workShiftMessages.basic.ERR_NOTALLOWED'), null);
            }

            //check whether any waork pattern link with the workshift
            $relatedWorkPatternRecords = $this->store->getFacade()::table('workPatternWeekDay')
                ->where('workShiftId', $id)
                ->first();
            if (!empty($relatedWorkPatternRecords)) {
                return $this->error(502, Lang::get('workShiftMessages.basic.ERR_NOTALLOWED'), null);
            }

            //check whether any employee shift related to this shift
            $relatedEmployeeShiftRecords = $this->store->getFacade()::table('employeeShift')
                ->where('workShiftId', $id)
                ->first();
            if (!empty($relatedEmployeeShiftRecords)) {
                return $this->error(502, Lang::get('workShiftMessages.basic.ERR_NOTALLOWED'), null);
            }

            $workShift = $this->store->getFacade()::table('workShifts')->where('id', $id)->delete();

            if (!$workShift ) {
                return $this->error(502, Lang::get('workShiftMessages.basic.ERR_DELETE'), $id);
            }

            return $this->success(200, Lang::get('workShiftMessages.basic.SUCC_DELETE'), ['id' => $id]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, $e->getMessage(), null);
        }
    }
     /** 
     * Following function retrive workshift info for dayTypeId
     * 
     * @param $data array containing $workshiftId and $dayTypeId
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "All workShift retrieved Successfully.",
     *      $data => {breakTime: "40",code: "SHIFT_00Z",deductLateFromOvertime: false,endTime: "12:00",
     *          gracePeriod: "10",halfDayLength: "",hasMidnightCrossOver: false,inOvertime: true
     *         isActive: false,isOTEnabled: true,minimumOT: "30",name: "SHIFT _Z",noOfDay: "0.5 Day",outOvertime: true
     *         roundOffMethod: "NO_ROUNDING",shiftType: "GENERAL",startTime: "06:00",workHours: "320"}]
     *      ] 
     */
    public function getWorkShiftDayType($data) {
       try {
       
          $workShift =$this->store->getFacade()::table('workShifts')
            ->leftJoin('workShiftDayType', 'workShiftDayType.workShiftId' ,"=",'workShifts.id')
            ->where('workShifts.id', $data['workshiftId'])
            ->where('workShiftDayType.dayTypeId',$data['dayTypeId'])
            ->first();
            if (!is_null($workShift)) {
               $workShift->payData = $this->getPayConfiguration($data['workshiftId'],$data['dayTypeId']);

               //get payConfigData
                $payConfigData =$this->store->getFacade()::table('workShiftPayConfiguration')
                ->where('workShiftPayConfiguration.workShiftId', $data['workshiftId'])
                ->where('workShiftPayConfiguration.workCalendarDayTypeId', $data['dayTypeId'])
                ->first();
                if (!is_null($payConfigData)) {
                    $workShift->payConfigType = $payConfigData->payConfigType;
                }
                $workShift->breakTime = (!is_null($workShift->breakTime)) ? $workShift->breakTime : 0;
                $workShift->workHours = (!is_null($workShift->workHours)) ? $workShift->workHours : 0;
            }



          return $this->success(200, Lang::get('workShiftMessages.basic.SUCC_SINGLE_RETRIVE'), $workShift);
       } catch (Exception $e) {
          Log::error($e->getMessage());
           return $this->error($e->getCode(), Lang::get('workShiftMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }

    /** 
     * Following function retrive all workShifts with name and id.
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "All workShift retrieved Successfully.",
     *       $data => [{
     *         id: 1
     *         name: "SHIFT _Z",
     *      },...]
     * ] 
     */
    public function getWorkShiftsList() {
        try {
            $workShift =[];
            $workShift =$this->store->getFacade()::table('workShifts')
                ->select('name','id')
                ->orderBy('workShifts.name','asc')
                ->get();

            return $this->success(200, Lang::get('workShiftMessages.basic.SUCC_ALL_RETRIVE'), $workShift);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workShiftMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
  
     /**
     * Following function creates a adhoc workShift.
     *
     * @param $workShifts array containing the workShifts data
     * @return int | String | array
     *
     * Usage:
     * $workShifts => [{"empId": 1,"date" : "2022-02-10" , shiftId:"2"}, ..]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "ShiftChange Successuflly",
     * $data => [{"empId": 1,"date" : "2022-02-10" , shiftId:"2"}]
     *  */

    public function createAdhocWorkShift($workShifts)
     {
        try {
            DB::beginTransaction();
            $adhocWorkShiftData =[];
            $shiftDetails =[];
            foreach( $workShifts['data'] as $workShiftData) {

                $checkShiftExists = DB::table('adhocWorkshifts')->where('date',$workShiftData['date'])->where('employeeId',$workShiftData['empId'])->first();
                $workShiftId = isset($workShifts['shiftId']) ? $workShifts['shiftId'] : NULL;
               
                if (!empty($checkShiftExists)) {
                    $adhocWorkShift = $this->store->getFacade()::table('adhocWorkshifts')->where('id',$checkShiftExists->id)->delete();       
                }
                $adhocWorkShiftArray = [  
                    'workshiftId' => $workShiftId, 
                    'date'       => $workShiftData['date'],
                    'employeeId' => $workShiftData['empId']
                ];    

                $adhocWorkShiftData [] = $adhocWorkShiftArray;
               

              $shiftDetails[$workShiftData['empId']][] = $workShiftData['date'];

            } 
            $adhocWorkShift = $this->store->getFacade()::table('adhocWorkshifts')->insert($adhocWorkShiftData);
         

            if (!empty($shiftDetails)) {
                foreach ($shiftDetails as $key => $dates) {
                  // run attendance process
                  $tenantId = $this->session->getTenantId();
                  $data = [
                    'tenantId' => $tenantId,
                    'employeeId' => $key,
                    'dates' => $dates
                  ];
                  dispatch(new BackDatedAttendanceProcess($data));
                }
            }

           
            DB::commit();

            return $this->success(201, Lang::get('workShiftMessages.basic.SUCC_SHIFT_CHANGE_CREATE'), $adhocWorkShift);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workShiftMessages.basic.ERR_SHIFT_CHANGE_CREATE'), null);
        }
    }

    /*following function is to add payConfiguration for the given workshift Id */
    public function workShiftPayConfig($id,$payConfigData, $dayTypeId, $payConfigType) {
        try {

            $dataSet = json_decode($payConfigData['payConfigData']);
            $workShiftId = $id;
            $oldDayTypeConfigDataIds = [];
            $thresholdData = [];
            $oldDayTypeConfigData = $this->store->getFacade()::table('workShiftPayConfiguration')
                        ->where('workShiftId', $workShiftId)
                        ->where('workCalendarDayTypeId', $dayTypeId)
                        ->get('workShiftPayConfiguration.id');
            foreach ($oldDayTypeConfigData as $key => $oldDayTypeConfig) {
                $oldDayTypeConfig = (array) $oldDayTypeConfig;
                $oldDayTypeConfigDataIds[] = $oldDayTypeConfig['id'];
            }

            $deletedDayTypeConfigs = array_values(array_diff($oldDayTypeConfigDataIds,$payConfigData['selectedOldDayTypeIds']));

            if (!empty($deletedDayTypeConfigs)) {
                $affectedThresholdRows = DB::table('workShiftPayConfigurationThreshold')->whereIn('workShiftPayConfigurationId', $deletedDayTypeConfigs)->delete();
                $affectedConfigRows = DB::table('workShiftPayConfiguration')->whereIn('id', $deletedDayTypeConfigs)->delete();
                
            }


            foreach ($dataSet as $key => $value) {
                $value = (array) $value;
                $workShiftConfigData = [
                    "workShiftId" => $workShiftId,
                    'workCalendarDayTypeId' => $value['dayTypeId'],
                    "payConfigType" => $payConfigType
                ];
                $payConfigId = null;
                if ($value['id'] == 'new') {
                    //create new record 
                    $payConfigData = $this->store->getFacade()::table('workShiftPayConfiguration')
                        ->where('workShiftId', $workShiftId)
                        ->where('workCalendarDayTypeId', $value['dayTypeId'])
                        ->first();
                    
                    if (empty($payConfigData)) {
                        $payConfigId = DB::table('workShiftPayConfiguration')
                            ->insertGetId($workShiftConfigData );
                    }

                    if (!empty($payConfigId)) {

                        foreach ($value['payTypeThresholdDetails'] as $key => $threshold) {
                            $threshold = (array) $threshold;

                            $thresholdData = [
                                "workShiftPayConfigurationId" => $payConfigId,
                                'payTypeId' => $threshold['payTypeId'],
                                'hoursPerDay' => $threshold['hoursPerDay'],
                                'thresholdSequence' => $threshold['thresholdKey'],
                                'thresholdType' => $threshold['thresholdType'],
                                'validTime' => isset($threshold['validTimeString']) ? $threshold['validTimeString']: null
                            ];

                            $payThresholdData = $this->store->getFacade()::table('workShiftPayConfigurationThreshold')
                                ->where('workShiftPayConfigurationId', $payConfigId)
                                ->where('payTypeId', $threshold['payTypeId'])
                                ->first();

                            if (empty($payThresholdData)) {
                                $thresholdDataId = DB::table('workShiftPayConfigurationThreshold')
                                    ->insertGetId($thresholdData);
                            }
                            
                        }

                    }

                } else {
                    $payConfigId  = $value['id'];
                    $oldPayThresholdDataIds = [];
                    $oldPayThresholdData = $this->store->getFacade()::table('workShiftPayConfigurationThreshold')
                        ->where('workShiftPayConfigurationId', $payConfigId)
                        ->get('workShiftPayConfigurationThreshold.id');
                    foreach ($oldPayThresholdData as $key => $oldPayThreshold) {
                        $oldPayThreshold = (array) $oldPayThreshold;
                        $oldPayThresholdDataIds[] = $oldPayThreshold['id'];
                    }

                    
                    $deletedPayThreshold = array_values(array_diff($oldPayThresholdDataIds,$value['selectedOldThresholdIds']));
                    if (!empty($deletedPayThreshold)) {
                        $affectedRows = DB::table('workShiftPayConfigurationThreshold')->whereIn('id', $deletedPayThreshold)->delete();
                    }

                    foreach ($value['payTypeThresholdDetails'] as $key => $threshold) {
                        $threshold = (array) $threshold;

                        $thresholdData = [
                            "workShiftPayConfigurationId" => $payConfigId,
                            'payTypeId' => $threshold['payTypeId'],
                            'hoursPerDay' => $threshold['hoursPerDay'],
                            'thresholdSequence' => $threshold['thresholdKey'],
                            'thresholdType' => $threshold['thresholdType'],
                            'validTime' => isset($threshold['validTimeString']) ? $threshold['validTimeString']: null
                        ];

                        if ($threshold['id'] == 'new') {
                            $payThresholdData = $this->store->getFacade()::table('workShiftPayConfigurationThreshold')
                                ->where('workShiftPayConfigurationId', $payConfigId)
                                ->where('payTypeId', $threshold['payTypeId'])
                                ->first();
    
                            if (empty($payThresholdData)) {
                                $thresholdDataId = DB::table('workShiftPayConfigurationThreshold')
                                    ->insertGetId($thresholdData);
                            }
                        } else {
                            $updateThreshold = DB::table('workShiftPayConfigurationThreshold')
                                ->where('id', $threshold['id'])
                                ->update($thresholdData);
                        }
                    }

                }
            }
            return $thresholdData;
        } catch (Exception $e) {
          Log::error($e->getMessage());
          return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }
   
    /**get the payconfig data for the given shiftId
     * 
     * @param $id and $dayTypeId
     * 
     * Usage:
     *  $id = 1 // workshiftId
     *  $dayTypeId = 1 
     **/
    private function getPayConfiguration($id, $dayTypeId) {
        try {
            $workShiftRealteDayTypes = $this->store->getFacade()::table('workShiftPayConfiguration')
                ->leftJoin('workCalendarDayType', 'workCalendarDayType.id', '=', 'workShiftPayConfiguration.workCalendarDayTypeId')
                ->where('workShiftPayConfiguration.workCalendarDayTypeId',$dayTypeId)
                ->where('workShiftId', $id)
                ->select(['workCalendarDayType.name', 'workCalendarDayType.shortCode', 'workShiftPayConfiguration.id', 'workShiftPayConfiguration.workCalendarDayTypeId'])
                ->get();

            $payTypes = $this->store->getFacade()::table('payType')->where('isDelete', false)->get();
            $commonPayTypeArray = [];

            foreach ($payTypes as $key => $payType) {
               $payType = (array) $payType;
               $tempPayTypeArr = [
                  'payTypeId' => $payType['id'],
                  'name' => $payType['name'],
                  'code' => $payType['code'],
                  'disabled' => false,
                ];

                $payTypeKey = 'key'. $payType['id'];
                $commonPayTypeArray[$payTypeKey] = $tempPayTypeArr;
            }
   
            $processedDatset = [];
    
            if (!empty($workShiftRealteDayTypes)) {
                $selectedOptionArr = [];

                foreach ($workShiftRealteDayTypes as $key => $value) {
                   $value = (array) $value;
                   $temp = [
                      'id' => $value['id'],
                      'dayTypeId' => $value['workCalendarDayTypeId'],
                      'disabled' => true,
                      'name' => $value['name'],
                      'shortCode' => $value['shortCode'],
                      'payTypeDetails'=> [],
                      'payTypeEnumList' => []
                    ];

                    $selectedOptionArr[] = $value['workCalendarDayTypeId'];

                    $dayTypeWisePayType = $commonPayTypeArray;

                    //get related threshold
                    $relatedThresholdData = $this->store->getFacade()::table('workShiftPayConfigurationThreshold')
                       ->where('workShiftPayConfigurationId', $value['id'])
                       ->get();

                    $thresholdArr = [];
                    foreach ($relatedThresholdData as $key => $threshold) {
                       $threshold = (array) $threshold;
                       $tempThesholdData = [
                          "id" => $threshold['id'],
                          "hoursPerDay" => $threshold['hoursPerDay'],
                          "payTypeId" => $threshold['payTypeId'],
                          "thresholdKey" => $threshold['thresholdSequence'],
                          "thresholdType" => $threshold['thresholdType'],
                          "showAddBtn" => false,
                          "validTime" => $threshold['validTime'],
                        
                        ];
                        $thresholdArr[] = $tempThesholdData;

                        //set disable pay type
                        $payKey = 'key'.$threshold['payTypeId'];
                        $dayTypeWisePayType[$payKey]['disabled'] = true;
                    }
                    if (sizeof($thresholdArr) == 4) {
                        $thresholdArr[0]['showAddBtn'] = false;
                        $thresholdArr[1]['showAddBtn'] = false;
                        $thresholdArr[2]['showAddBtn'] = false;
                        $thresholdArr[3]['showAddBtn'] = false;
                    } elseif (sizeof($thresholdArr) == 3) {
                        $thresholdArr[0]['showAddBtn'] = false;
                        $thresholdArr[1]['showAddBtn'] = false;
                        $thresholdArr[2]['showAddBtn'] = true;
                    } elseif (sizeof($thresholdArr) == 2) {
                        $thresholdArr[0]['showAddBtn'] = false;
                        $thresholdArr[1]['showAddBtn'] = true;
                    } else {
                        $thresholdArr[0]['showAddBtn'] = true;
                    }


                   $dayTypeWisePayType = array_values($dayTypeWisePayType);

                   $temp['payTypeDetails'] = $thresholdArr;
                   $temp['payTypeEnumList'] = $dayTypeWisePayType;
                
                   $processedDatset[] = $temp;
                }
            }
            $payConfigData = [
              'data' => $processedDatset,
              'selectedOptionArr' => $selectedOptionArr
            ];
        
           return $payConfigData;
        } catch (Exception $e) {
          Log::error($e->getMessage());
          return $this->success($e->getCode(), Lang::get('workShiftMessages.basic.ERR_GET_PAY_CONFIG'), []);
        }
    }
}

