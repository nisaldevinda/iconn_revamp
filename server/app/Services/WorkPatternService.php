<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Library\Session;
use App\Library\RelationshipType;
use App\Traits\JsonModelReader;
use App\Jobs\BackDatedAttendanceProcess;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use stdClass;

/**
 * Name: WorkPatternService
 * Purpose: Performs tasks related to the WorkPattern model.
 * Description: WorkPattern Service class is called by the WorkPatternController where the requests related
 * to WorkPattern Model (basic operations and others). Table that is being modified is  WorkPattern, WorkPatternWeek and  WorkPatternWeekDay.
 * Module Creator: Shobana
 */
class WorkPatternService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $workPatternModel;
    private $workPatternWeekModel;
    private $workPatternWeekDayModel;
    private $workPatternLocationModel;
    private $session;
    public function __construct(Store $store,  Session $session)
    {
        $this->store = $store;
        $this->workPatternModel = $this->getModel('workPattern', true);
        $this->workPatternWeekModel = $this->getModel('workPatternWeek', true);
        $this->workPatternWeekDayModel = $this->getModel('workPatternWeekDay', true);
        $this->workPatternLocationModel = $this->getModel('workPatternLocation',true);
        $this->workShiftModel = $this->getModel('workShifts', true);
        $this->session = $session;
    }

    /**
     * Following function create a Work Pattern.
     * 
     * @param $data array of work pattern data
     * 
     * Usage:
     * $data => ["name": "pattern"]
     * 
     * Sample output:
     * $statusCode => 201,
     * $message => "Work Pattern created Successuflly",
     * $data => {"name": "Pattern A", "description": "this is Pattern A"}
     *  */

    public function createWorkPattern($data)
    {
        try {
            $validationResponse = ModelValidator::validate($this->workPatternModel, $data, false);
           
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('workPatternMessages.basic.ERR_CREATE'), $validationResponse);
            }
            if (isset( $data['countryId'])) {
                $locationId = isset( $data['locationId']) && empty($data['locationId']);

                if ($locationId) { 
                    return $this->error(400, Lang::get('workPatternMessages.basic.ERR_CREATE_LOCATION_NOTEXIST'), null);
                }
            }
            
           DB::beginTransaction();
           $workPattern = $this->store->insert($this->workPatternModel, $data, true);
            
            // if(isset( $data['locationId'])) {
            //    $locationIds = $data['locationId'];
            //    foreach ($locationIds as $location) {
            //         $locationData = [];
            //         $locationData['workPatternId'] =  $workPattern['id'];
            //         $locationData['locationId'] = $location;
            //         $workPatternLocation = $this->store->insert($this->workPatternLocationModel, $locationData ,true);
            //     }
            // }
            $workPatternId = $workPattern['id'];
            $workWeekPattern = [];
            if (!empty($data["weekTable1"])) {
               $workWeekPattern['workPatternId'] =  $workPatternId;
               $workWeekPattern['workPatternWeekIndex'] = 1 ;
               $workWeekPatternData =  $this->store->insert($this->workPatternWeekModel, $workWeekPattern, true);
              
                $shiftDays = [
                    0 => null,
                    1 => null,
                    2 => null,
                    3 => null,
                    4 => null,
                    5 => null,
                    6 => null,
                ];
                foreach ($data['weekTable1'] as $weekData) {
                    $shiftDays[$weekData['day']] = $weekData;
                }
               
                foreach ($shiftDays as $dayTypeId => $shiftDay) {

                    $workPatternWeekDayRecord=[];
                    $workPatternWeekDayRecord['workPatternWeekId'] =  $workWeekPatternData['id'];
                    $workPatternWeekDayRecord['dayTypeId'] = $dayTypeId ;
                        $workPatternWeekDayRecord['workShiftId'] = $shiftDay !== null ? $shiftDay['shiftId'] : NULL;
                    $workWeekDaysPattern =  $this->store->insert($this->workPatternWeekDayModel, $workPatternWeekDayRecord, true);
                } 
            } 
            if (!empty($data["weekTable2"])) {
                
                $workWeekPattern['workPatternId'] = $workPatternId;
                $workWeekPattern['workPatternWeekIndex'] = 2 ;
                $workWeekPatternData =  $this->store->insert($this->workPatternWeekModel, $workWeekPattern, true);
               
                $shiftDays = [
                    0 => null,
                    1 => null,
                    2 => null,
                    3 => null,
                    4 => null,
                    5 => null,
                    6 => null,
                ];
                foreach ($data['weekTable2'] as $weekData) {
                    $shiftDays[$weekData['day']] = $weekData;
                }
                
               
                foreach ($shiftDays as $dayTypeId => $shiftDay) {
                   
                    $workPatternWeekDayRecord=[];
                    $workPatternWeekDayRecord['workPatternWeekId'] =  $workWeekPatternData['id'];
                    $workPatternWeekDayRecord['dayTypeId'] = $dayTypeId ;
                    $workPatternWeekDayRecord['workShiftId'] = $shiftDay !== null ? $shiftDay['shiftId'] : NULL;
                    $workWeekDaysPattern =  $this->store->insert($this->workPatternWeekDayModel, $workPatternWeekDayRecord, true);
                   
                }
            } 
            
            DB::commit();
            return $this->success(201, Lang::get('workPatternMessages.basic.SUCC_CREATE'), $workPattern);
            
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workPatternMessages.basic.ERR_CREATE'), null);
        }
    }

    /** 
     * Following function retrive Work Pattern by id.
     * 
     * @param $id email id
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Work Pattern retrieved Successfully",
     *      $data => {"name": "Pattern A", "description": "this is Pattern A"}
     * ]
     */
    public function getworkPattern($id)
    {
        try {
            $workPattern =[];
            $workPattern =$this->store->getFacade()::table('workPattern')->where('id', $id)->first();

            // $workPatternLocation = $this->store->getFacade()::table('workPatternLocation')
            //    ->leftJoin('location','location.id','=','workPatternLocation.locationId')
            //    ->where('workPatternId',$id)
            //    ->get();
            $locationId= [];
            $countryId =[] ;
            // foreach ($workPatternLocation as $location) {
            //     array_push($locationId,$location->locationId);
            //     array_push($countryId,$location->countryId);
            // }
            $workWeekId = $this->store->getFacade()::table('workPatternWeek')->where('workPatternId', $id)->get()->all();
            $worWeekDayContent = [];
            foreach ($workWeekId as $weekDay) {
              $workWeekDayId =  $this->store->getFacade()::table('workPatternWeekDay')
                     ->leftJoin('workShifts','workShifts.id',"=", 'workPatternWeekDay.workShiftId')
                     ->leftJoin('dayOfWeek', 'dayOfWeek.id', '=', 'workPatternWeekDay.dayTypeId')
                     ->where('workPatternWeekId',$weekDay->id)->get()->all();
              array_push($worWeekDayContent, $workWeekDayId);
            }
            $workPattern->worWeekDayContent= $worWeekDayContent;
            $workPattern->location =  $locationId;
            $workPattern->country =   array_unique($countryId);
            if (is_null($workPattern)) {
                return $this->error(404, Lang::get('workPatternMessages.basic.ERR_NONEXISTENT'), $workPattern);
            }

            return $this->success(200, Lang::get('workPatternMessages.basic.SUCC_SINGLE_RETRIVE'), $workPattern);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workPatternMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }
    
   
    /** 
     * Following function retrive all work patterns.
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "All Work Pattern retrieved Successfully.",
     *      $data => [{"name": "pattern A", "description": "this is pattern A"}]
     * ] 
     */
    public function listworkPatterns($permittedFields, $options)
    { 
        try {
            $filteredPattern = $this->store->getAll(
                $this->workPatternModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('workPatternMessages.basic.SUCC_ALL_RETRIVE'), $filteredPattern);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workPatternMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    
    /** 
    * following creates duplicate work pattern


   */
    public function createDuplicatePattern($data)
    {
        try {
       
           DB::beginTransaction();
           $workPatternExist =$this->store->getFacade()::table('workPattern')->where('name', $data['newWorkPatternName'])->first(); 
          
           if (!empty($workPatternExist)) {
               return $this->error(400, Lang::get('workPatternMessages.basic.ERR_EXISTS'), $workPatternExist);
            }
           $workPattern = $this->store->getFacade()::table('workPattern')->where('name', $data['duplicatedFrom'])->first();
           $duplicateWorkPattern = [];
           $duplicateWorkPattern['name'] = $data['newWorkPatternName'];
           $duplicateWorkPattern['description'] =  $workPattern->description;
           $duplicateWorkPattern = $this->store->insert($this->workPatternModel,  $duplicateWorkPattern, true);
           $workWeekPattern = $this->store->getFacade()::table('workPatternWeek')->where('workPatternId',$workPattern->id)->get()->all();
          
           foreach ($workWeekPattern as $workWeekPatternData) {
               $workWeekDuplicatePattern = [];
               $workWeekDuplicatePattern['workPatternId'] = $duplicateWorkPattern['id'];
               $workWeekDuplicatePattern['workPatternWeekIndex'] = $workWeekPatternData->workPatternWeekIndex;
               $workWeekDuplicatePattern =  $this->store->insert($this->workPatternWeekModel, $workWeekDuplicatePattern, true);

                $workWeekdayPattern = $this->store->getFacade()::table('workPatternWeekDay')
                    ->leftJoin('workShifts','workShifts.id',"=",'workPatternWeekDay.workShiftId')               
                    ->where('workPatternWeekId', $workWeekPatternData->id)->get()->all();

                foreach ($workWeekdayPattern  as $workWeekdayPatternData) {
                   
                    
                    $workWeekDayDuplicatePattern = [] ;
                    $workWeekDayDuplicatePattern['workShiftId']  = $workWeekdayPatternData->workShiftId;
                    $workWeekDayDuplicatePattern['workPatternWeekId'] = $workWeekDuplicatePattern['id'];
                    $workWeekDayDuplicatePattern['dayTypeId']  = $workWeekdayPatternData->dayTypeId;
                    $duplicateworkWeekDayPattern =  $this->store->insert($this->workPatternWeekDayModel,  $workWeekDayDuplicatePattern, true);
                }
           }
           DB::commit();
           return $this->success(201, Lang::get('workPatternMessages.basic.SUCC_CREATE'),  $duplicateWorkPattern);
        } catch(Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workPatternMessages.basic.ERR_CREATE'), null);
        }

    }

    /**
     * Following function updates work pattern.
     * 
     * @param $id work pattern id
     * @param $data array containing work pattern data
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "work pattern updated successfully.",
     *      $data => {"name": "pattern A", "description": "this is pattern A", content: "<p>Hi #first_name#</p>"}
     * 
     */

    public function updateworkPattern($id, $data)
    {
        try {      
            $data['id'] = $id;   
            $validationResponse = ModelValidator::validate($this->workPatternModel, $data, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('workPatternMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $dbTemplate = $this->store->getFacade()::table('workPattern')->where('id', $id)->first();
            if (is_null($dbTemplate)) {
                return $this->error(404, Lang::get('workPatternMessages.basic.ERR_NONEXISTENT'), $data);
            }
            if (isset( $data['countryId'])  && !empty($data['locationId'])) {
                $locationId = isset( $data['locationId']) && empty($data['locationId']);

                if ( $locationId) { 
                    return $this->error(400, Lang::get('workPatternMessages.basic.ERR_UPDATE_LOCATION_NOTEXIST'), null);
                }
            }
            
            DB::beginTransaction();
            $workPattern = $this->store->updateById($this->workPatternModel, $id, $data);
            // if (isset($data['locationId'])) {
            //    $workPatternLocationExists =  $this->store->getFacade()::table('workPatternLocation')->where('workPatternId', $id)->get();
               
            //    if (!empty($workPatternLocationExists)) { 
            //        foreach($workPatternLocationExists as $workPatternLocation) {
            //             $this->store->deleteById($this->workPatternLocationModel, $workPatternLocation->id);
            //        }
            //    } 

            //    $locationIds = $data['locationId'];
            //     foreach ($locationIds as $location) {
            //        $locationData = [];
            //        $locationData['workPatternId'] = $id;
            //        $locationData['locationId'] = $location;
            //        $workPatternLocation = $this->store->insert($this->workPatternLocationModel, $locationData ,true);
            //     }
            // }
            if (!empty($data["weekTable1"])) {
                $weekIndexId = 1;
                $workWeekPatternData =  $this->store->getFacade()::table('workPatternWeek')->where('workPatternId',$id)->where('workPatternWeekIndex',$weekIndexId)->first();
                
                $shiftDays = [
                    0 => null,
                    1 => null,
                    2 => null,
                    3 => null,
                    4 => null,
                    5 => null,
                    6 => null,
                ];
                foreach ($data['weekTable1'] as $weekData) {
                    $shiftDays[$weekData['day']] = $weekData;
                }
                
                if (empty($workWeekPatternData)) {
                    $workWeekPattern['workPatternId'] =  $id;
                    $workWeekPattern['workPatternWeekIndex'] = 1 ;
                    $workWeekPatternData =  $this->store->insert($this->workPatternWeekModel, $workWeekPattern, true);
                    
                   
                    
                    foreach ($shiftDays as $dayTypeId => $shiftDay) {
                      
                        $workPatternWeekDayRecord=[];
                        $workPatternWeekDayRecord['workPatternWeekId'] =  $workWeekPatternData['id'];
                        $workPatternWeekDayRecord['dayTypeId'] = $dayTypeId ;
                        $workPatternWeekDayRecord['workShiftId'] = $shiftDay !== null &&  $shiftDay['shiftId'] !== '' ? $shiftDay['shiftId'] : NULL;
                        $workWeekDaysPattern =  $this->store->insert($this->workPatternWeekDayModel, $workPatternWeekDayRecord, true);
                       
                    
                    }
                
                } else {
  
                    foreach ($shiftDays as $dayTypeId => $shiftDay) {
                     
                       $checkWorkWeekDayExist = $this->store->getFacade()::table('workPatternWeekDay')->where('workPatternWeekId',$workWeekPatternData->id)->where('dayTypeId' ,$dayTypeId)->first();
                       if (!empty($checkWorkWeekDayExist) && ($checkWorkWeekDayExist->workShiftId !== null)) {
                          $workShiftId =$shiftDay !== null &&  $shiftDay['shiftId'] !== '' ? $shiftDay['shiftId'] : NULL;
                            $checkWorkWeekDayExist = $this->store->getFacade()::table('workPatternWeekDay')
                                ->where('workPatternWeekId',$workWeekPatternData->id)
                                ->where('dayTypeId' , $dayTypeId )
                                ->update(['workShiftId'=>  $workShiftId ]);
                           
                        } else {
                            
                            $workPatternWeekDayRecord=[];
                            $workPatternWeekDayRecord['workShiftId'] =$shiftDay !== null &&  $shiftDay['shiftId'] !== '' ? $shiftDay['shiftId'] : NULL ;
                            $workWeekDaysPattern = $this->store->getFacade()::table('workPatternWeekDay')->where('id',$checkWorkWeekDayExist->id)->update($workPatternWeekDayRecord);
                            
                        }
                
                    }
                }
            }   
            
            if (!empty($data["weekTable2"]) || $data['clone']) {
            
                $weekIndexId = 2;
                $workWeekPatternData =  $this->store->getFacade()::table('workPatternWeek')->where('workPatternId',$id)->where('workPatternWeekIndex',$weekIndexId)->first();
                
                $shiftDays = [
                    0 => null,
                    1 => null,
                    2 => null,
                    3 => null,
                    4 => null,
                    5 => null,
                    6 => null,
                ];
                foreach ($data['weekTable2'] as $weekData) {
                    $shiftDays[$weekData['day']] = $weekData;
                }
                
                   if (empty($workWeekPatternData)) {
                       $workWeekPattern['workPatternId'] =  $id;
                       $workWeekPattern['workPatternWeekIndex'] = 2 ;
                       $workWeekPatternData =  $this->store->insert($this->workPatternWeekModel, $workWeekPattern, true);
                       
                       
                       
                       foreach ($shiftDays as $dayTypeId => $shiftDay) {
                          
                            $workPatternWeekDayRecord=[];
                            $workPatternWeekDayRecord['workPatternWeekId'] =  $workWeekPatternData['id'];
                            $workPatternWeekDayRecord['dayTypeId'] =  $dayTypeId ;
                            $workPatternWeekDayRecord['workShiftId'] = $shiftDay !== null &&  $shiftDay['shiftId'] !== ''? $shiftDay['shiftId'] : NULL;
                            $workWeekDaysPattern =  $this->store->insert($this->workPatternWeekDayModel, $workPatternWeekDayRecord, true);
                          
                        }
                
                    } else {
  
             
                        foreach ($shiftDays as $dayTypeId => $shiftDay) {
                        
                           $checkWorkWeekDayExist = $this->store->getFacade()::table('workPatternWeekDay')->where('workPatternWeekId',$workWeekPatternData->id)->where('dayTypeId' ,$dayTypeId)->first();
                           if (!empty($checkWorkWeekDayExist) && ($checkWorkWeekDayExist->workShiftId !== null)) {
                            $workShiftId = $shiftDay !== null &&  $shiftDay['shiftId'] !== '' ? $shiftDay['shiftId'] : NULL;
                            $checkWorkWeekDayExist = $this->store->getFacade()::table('workPatternWeekDay')
                                    ->where('workPatternWeekId',$workWeekPatternData->id)
                                    ->where('dayTypeId' , $dayTypeId )
                                    ->update(['workShiftId'=> $workShiftId]);
                               
                            } else {
                                $workPatternWeekDayRecord=[];
                                $workPatternWeekDayRecord['workShiftId'] = $shiftDay !== null &&  $shiftDay['shiftId'] !== '' ? $shiftDay['shiftId'] : NULL;
                                $workWeekDaysPattern = $this->store->getFacade()::table('workPatternWeekDay')->where('id',$checkWorkWeekDayExist->id)->update($workPatternWeekDayRecord);
                            }
                
                        }
                    }  
               
            }   
            if (!$workPattern) {
                return $this->error(502, Lang::get('workPatternMessages.basic.ERR_UPDATE'), $id);
            }
            DB::commit();
            return $this->success(200, Lang::get('workPatternMessages.basic.SUCC_UPDATE'), $data);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workPatternMessages.basic.ERR_UPDATE'), null);
        }
    }

    /** 
     * Delete Work Pattern by id.
     * 
     * @param $id email id
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => " Work Pattern deleted Successfully",
     *      $data => {id: 1}
     * ]
     */
    public function deleteworkPattern($id)
    {
        try {
            DB::beginTransaction();
            $workPattern = $this->store->getFacade()::table('workPattern')->where('id', $id)->first();
            if (is_null($workPattern)) {
                return $this->error(404, Lang::get('workPatternMessages.basic.ERR_DELETE'), null);
            }

            //check whether has any employee assign for this work pattern
            $relatedEmployeeWorkPatternRecords = $this->store->getFacade()::table('employeeWorkPattern')
                ->where('workPatternId', $id)
                ->first();

            if (!empty($relatedEmployeeWorkPatternRecords)) {
                return $this->error(502, Lang::get('workPatternMessages.basic.ERR_NOTALLOWED'), null);
            }

            $deleteWorkPatternRecord = $this->store->deleteById($this->workPatternModel, $id, true);
            
            $workPatternWeekId = $this->store->getFacade()::table('workPatternWeek')->where('workPatternId',$workPattern->id)->pluck('id')->toArray();
            $deleteWorkPatternWeekRecord =  $this->store->getFacade()::table('workPatternWeek')->where('workPatternId',$workPattern->id)->delete();
            
            $deleteWorkPatternWeekDayRecord  = $this->store->getFacade()::table('workPatternWeekDay')->whereIn('workPatternWeekId', $workPatternWeekId)->delete();
          
            DB::commit();
            return $this->success(200, Lang::get('workPatternMessages.basic.SUCC_DELETE'), ['id' => $id]);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return $this->error(400, $e->getMessage(), null);
        }
    }

   /**
    * Delete week in a work pattern
    */

    public function deleteWeek($id,$data) {
       
        try {
            $dbTemplate = $this->store->getFacade()::table('workPattern')->where('id', $id)->first();
            if (is_null($dbTemplate)) {
                return $this->error(404, Lang::get('workPatternMessages.basic.ERR_NONEXISTENT'), $data);
            }
             $workPatternWeekId = $this->store->getFacade()::table('workPatternWeek')->where('workPatternId',$id)->where('workPatternWeekIndex',$data['weekIndexId'])->first();
            if (is_null($workPatternWeekId)) {
                return $this->error(404, Lang::get('workPatternMessages.basic.ERR_NONEXISTENTWEEK'), $data);
            }
            $deleteworkPatternWeekId = $this->store->getFacade()::table('workPatternWeek')->where('workPatternId',$id)->where('workPatternWeekIndex',$data['weekIndexId'])->delete();
            
            $workPatternWeekShiftIds= $this->store->getFacade()::table('workPatternWeekDay')->where('workPatternWeekId',$workPatternWeekId->id)->pluck('workShiftId');
           
            $deletedRecords = $this->store->getFacade()::table('workPatternWeekDay')->where('workPatternWeekId',$workPatternWeekId->id)->delete();
            // $deleteShiftRecords = $this->store->getFacade()::table('workShifts')->whereIn('id',$workPatternWeekShiftIds)->delete();
           
            return $this->success(200, Lang::get('workPatternMessages.basic.WEEK_SUCC_DELETE'), ['id' => $id]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, $e->getMessage(), null);
        }
    }

     /** 
     * Following function retrive all work patterns for work schedule.
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "All Work Pattern retrieved Successfully.",
     *      $data => [{"id:"1","name": "pattern A"}]
     * ] 
     */
    public function listAllWorkPatterns()
    { 
        try {

            $filteredPattern = $this->store->getFacade()::table('workPattern')->select('id','name')->where('isDelete' ,false)->get();
            
            return $this->success(200, Lang::get('workPatternMessages.basic.SUCC_ALL_RETRIVE'), $filteredPattern);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workPatternMessages.basic.ERR_ALL_RETRIVE'), null);
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
    public function getWorkPaternRelatedEmployees($id)
    {
        try {

            $relatedEmployees = $this->store->getFacade()::table('employeeWorkPattern')
                ->leftJoin('employee', 'employee.id' ,"=",'employeeWorkPattern.employeeId')
                ->select(
                   'employeeWorkPattern.*',
                   DB::raw("CONCAT_WS(' ', employee.firstName, employee.middleName, employee.lastName) AS employeeName"),
                   'employee.id'
                )
                ->where('employeeWorkPattern.workPatternId', $id)
                ->where('employeeWorkPattern.isActivePattern', true)
                ->groupBy("employeeWorkPattern.employeeId")
                ->get();

            return $this->success(200, Lang::get('shiftAssignMessages.basic.SUCC_SINGLE_RETRIVE'), $relatedEmployees);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('shiftAssignMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }

    /**
     * Following function create a Assigning Work pattern to employees.
     * 
     * @param $data array of workPatternAssign data
     * 
     * Usage:
     * $data => {
     *    'workPatternId' => 1,
     *    'selectedEmployees' => [2,4],
     *    'effectiveDate' =>  '2022-02-05'
     * 
     * }
     * 
     * Sample output:
     * $statusCode => 201,
     * $message => "workPatternAssign created Successuflly",
     * $data => {"workPatternId": "1", employeeId: [4,5]}
     *  */

    public function assignWorkPatterns($data)
    {
        try {
            DB::beginTransaction();
            $shiftAssign = [];
            $employeeIds =  isset($data['selectedEmployees']) ? $data['selectedEmployees'] : [];
           
            $assignedWorkPatternEmployeesIds = $this->store->getFacade()::table('employeeWorkPattern')
                                    ->where('employeeWorkPattern.workPatternId', $data['workPatternId'])
                                    ->where('isActivePattern', true)->pluck('employeeId')->toArray();
            
            $newEmployeeIds = array_diff($employeeIds , $assignedWorkPatternEmployeesIds);
            $removeOldEmployeeIds = array_diff($assignedWorkPatternEmployeesIds ,$employeeIds );

            if (!empty($newEmployeeIds)) {
               
                $workPatternExsist = $this->store->getFacade()::table('employeeWorkPattern')->where('isActivePattern', true)->whereIn('employeeId',$newEmployeeIds)->get();

                if ($workPatternExsist->isNotEmpty()) {
                    DB::rollback();
                    return $this->error(400, Lang::get('shiftAssignMessages.basic.ERR_SHIFT_EXIST'), NULL);
                }

                if (!isset($data['effectiveDate'])) {
                    DB::rollback();
                    return $this->error(400, Lang::get('shiftAssignMessages.basic.ERR_EFFECTIVE_DATE'), NULL);
                }
                
                $workPatternData = [] ;
                foreach ($newEmployeeIds as $empId) {
                    //check whether this employee has any pre assigned workpattern that greater than the current effective date
                    $needToDeleteEmpWorkPatternCount =  $this->store->getFacade()::table('employeeWorkPattern')
                    ->where('employeeId', $empId)->where('effectiveDate','>=',$data['effectiveDate'])->count();

                    if ($needToDeleteEmpWorkPatternCount > 0) {
                        $deleteGreaterEffectiveDateRecords = $this->store->getFacade()::table('employeeWorkPattern')
                        ->where('employeeId', $empId)->where('effectiveDate','>=',$data['effectiveDate'])->delete();
                    }


                    $workPatternAssignArray = [
                       'workPatternId' => $data['workPatternId'],
                       'employeeId' => $empId,
                       'isActivePattern' => true,
                       'effectiveDate' => isset($data['effectiveDate']) ? $data['effectiveDate'] : ''
                    ];
                    $workPatternData [] = $workPatternAssignArray;
                }
                $workPatternAssign =  $this->store->getFacade()::table('employeeWorkPattern')->insert($workPatternData);
            }
            
            if (!empty($removeOldEmployeeIds)) {
              
                $updateIsActivePatternState =  $this->store->getFacade()::table('employeeWorkPattern')
                    ->where('workPatternId' ,$data['workPatternId'])
                    ->whereIn('employeeId', $removeOldEmployeeIds)
                    ->update(['isActivePattern' => false]);
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
            DB::commit();
            return $this->success(201, Lang::get('shiftAssignMessages.basic.SUCC_CREATE'), $shiftAssign);
           
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('shiftAssignMessages.basic.ERR_CREATE'), null);
        }
    }
}

