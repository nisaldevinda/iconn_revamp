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
 * Name: LeaveTypeConfig
 * Purpose: Performs tasks related to the Leave Type model.
 * Description: Leave Type Service class is called by the LeaveTypeController where the requests related
 * to User Leave Type Model (CRUD operations and others).
 * Module Creator: Tharindu Darshana
 */
class LeaveTypeConfigService extends BaseService
{
    use JsonModelReader;
    use ConfigHelper;
    use EmployeeHelper;

    private $store;
    protected $session;

    private $leaveEmployeeGroupModel;
    private $leaveEntitlementModel;

    public function __construct(Store $store, Session $session)
    {
        $this->store = $store;
        $this->session = $session;

        $this->leaveEmployeeGroupModel = $this->getModel('leaveEmployeeGroup', true);
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

    public function createWhoCanApply($whoCanApply)
    {
        try {

            if (!isset($whoCanApply['name'])) {
                $data =[
                    'name'=> ["Required"]
                ];
                return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_REQUIRED'),  $data);
            }

            if (isset($whoCanApply['name'])) {
                // check Employee Group Name exist for the same leave Type
                $checkEmployeeGroupExist =  $this->store->getFacade()::table('leaveEmployeeGroup')
                   ->where("leaveTypeId",$whoCanApply['leaveTypeId'] )
                   ->where("name",$whoCanApply['name'])
                   ->first();
                if (!empty($checkEmployeeGroupExist)) {
                    $data =[
                        'name'=> ["This is already existing."]
                    ];
                    return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_UNIQUE'),  $data);
                }
            }
            if(isset( $whoCanApply['jobTitles'])){
                $leaveType['jobTitles']=json_encode($whoCanApply['jobTitles']);

            }
            if(isset( $whoCanApply['employmentStatuses'])){
                $leaveType['employmentStatuses']=json_encode($whoCanApply['employmentStatuses']);

            }
            if(isset( $whoCanApply['locations'])){
                $leaveType['locations']=json_encode($whoCanApply['locations']);

            }
            if(isset( $whoCanApply['genders'])){
                $leaveType['genders']=json_encode($whoCanApply['genders']);

            }
            if(isset( $whoCanApply['name'])){
                $leaveType['name']=$whoCanApply['name'];

            }
            if(isset( $whoCanApply['comment'])){
                $leaveType['comment']=$whoCanApply['comment'];

            }
            if(isset( $whoCanApply['minPemenancyPeriod'])){
                $leaveType['minPemenancyPeriod']=$whoCanApply['minPemenancyPeriod'];

            }
            if(isset( $whoCanApply['minServicePeriod'])){
                $leaveType['minServicePeriod']=$whoCanApply['minServicePeriod'];

            }
           
            $leaveType['leaveTypeId']=$whoCanApply['leaveTypeId'];
            $existingId = $this->store->getFacade()::table('leaveEmployeeGroup')
            ->where("leaveTypeId",$whoCanApply['leaveTypeId'] )
            ->first();


            // if(is_null($existingId)){
            //     $newLeaveType =  $this->store->getFacade()::table('leaveEmployeeGroup')
            //     ->insert($leaveType);
            // }
            // else{
            //     $newLeaveType =  $this->store->getFacade()::table('leaveEmployeeGroup')
            //     ->where('id',$existingId->id)
            //     ->update($leaveType);
            // }
            $newLeaveType =  $this->store->getFacade()::table('leaveEmployeeGroup')
                 ->insert($leaveType);
            
            return $this->success(201, Lang::get('leaveEmployeeGroupMessages.basic.SUCC_CREATE'), $whoCanApply);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'), null);
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
    public function getWhoCanApply($id)
    {
        try {
           // $leaveType = $this->store->getById($this->leaveEmployeeGroupModel, $id);
            $whoCanApply = $this->store->getFacade()::table('leaveEmployeeGroup')
            ->where("leaveTypeId",$id )
            ->first();

            if (is_null($whoCanApply)) {
                return $this->success(200, Lang::get('leaveTypeMessages.basic.SUCC_GET'), $whoCanApply);
            }

            $leaveType['jobTitles']=json_decode($whoCanApply->jobTitles);
             $leaveType['employmentStatuses']=json_decode($whoCanApply->employmentStatuses);
             $leaveType['genders']=json_decode($whoCanApply->genders);
             $leaveType['locations']=json_decode($whoCanApply->locations);
             $leaveType['leaveTypeId']=$whoCanApply->leaveTypeId;
             $leaveType['minPemenancyPeriod']=$whoCanApply->minPemenancyPeriod;
             $leaveType['minServicePeriod']=$whoCanApply->minServicePeriod;

            return $this->success(200, Lang::get('leaveTypeMessages.basic.SUCC_GET'), $leaveType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveTypeMessages.basic.ERR_GET'), null);
        }
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

    public function getAllEmployeeGroups()
    {
        try {
            $leaveEmployeeGroup = $this->store->getAll(
                $this->leaveEmployeeGroupModel,
                [],
                [],
                [],
                [['isDelete','=',false]]
            );
          
            return $this->success(200, Lang::get('leaveEmployeeGroupMessages.basic.SUCC_GETALL'), $leaveEmployeeGroup);

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveEmployeeGroupMessages.basic.ERR_GET'), null);
        }

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

    public function getAllLeaveTypeWiseAccruals($leaveTypeId)
    {
        try {

            $leaveAccruals = DB::table('leaveAccrual')
                ->select('leaveAccrual.*', 'leaveEmployeeGroup.name')
                ->leftJoin('leaveEmployeeGroup','leaveEmployeeGroup.id','=','leaveAccrual.leaveEmployeeGroupId')
                ->where('leaveAccrual.leaveTypeId', $leaveTypeId)->get(); 

            foreach ($leaveAccruals as $key => $accrualDataset) {
                $accrualDataset = (array) $accrualDataset;
                if (!empty($accrualDataset['dayOfCreditingForAnnualFrequency'])) {
                    $annualFrequencyDataArr = explode("-",$accrualDataset['dayOfCreditingForAnnualFrequency']);
                    
                     if (!empty($annualFrequencyDataArr[0])) {
                         $annualFrequencyArr =  str_split($annualFrequencyDataArr[0]);
                             if (sizeof($annualFrequencyArr) > 0) {
                                $leaveAccruals[$key]->dayOfCreditingForAnnualFrequency = ($annualFrequencyArr[0] !== '0') ? (int) $annualFrequencyDataArr[0] : (int) $annualFrequencyArr[1];
                             } 
                     }
 
                     if (!empty($annualFrequencyDataArr[1])) {
                         $annualFrequencyDayValArr =  str_split($annualFrequencyDataArr[1]);
                             if (sizeof($annualFrequencyDayValArr) > 0) {
                                $leaveAccruals[$key]->dayValue = ($annualFrequencyDayValArr[0] !== '0') ? (int) $annualFrequencyDataArr[1] : (int) $annualFrequencyDayValArr[1];
                             } 
                     }
                 }
            }


            $res = [
                'data' => $leaveAccruals,
                'total' => sizeof($leaveAccruals)
            ];
           
          
            return $this->success(200, Lang::get('leaveEmployeeGroupMessages.basic.SUCC_GETALL'), $res);

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveEmployeeGroupMessages.basic.ERR_GET'), null);
        }

    }

    /**
     * Following function retrive all employee groups that realted to the give leave type
     *
     * @param $leaveTypeId
     * @return | array
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Leave Type Employee Group retrive successfully!",
     * $data => [{"name": "GP01", ...}]
     *  */

    public function getEmployeeGroupsByLeaveTypeId($leaveTypeId)
    {
        try {
            $leaveTypeRelateEmployeGroups = DB::table('leaveEmployeeGroup')
                ->where("leaveTypeId",$leaveTypeId)->where("isDelete",false)->get();


            $leaveTypeRelateEmployeGroupsArr = [];

            foreach ($leaveTypeRelateEmployeGroups as $key => $employeeGroup) {
                $employeeGroup = (array) $employeeGroup;

                $employeeGroup['jobTitles']=json_decode($employeeGroup['jobTitles']);
                $employeeGroup['employmentStatuses']=json_decode($employeeGroup['employmentStatuses']);
                $employeeGroup['genders']=json_decode($employeeGroup['genders']);
                $employeeGroup['locations']=json_decode($employeeGroup['locations']);
                $employeeGroup['leaveTypeId']=$employeeGroup['leaveTypeId'];
                $employeeGroup['minServicePeriodYear']=  (!empty($employeeGroup['minServicePeriod'])) ? intdiv($employeeGroup['minServicePeriod'], 12) : null;
                $employeeGroup['minServicePeriodMonth']= (!empty($employeeGroup['minServicePeriod'])) ? fmod($employeeGroup['minServicePeriod'], 12) : null;
                $employeeGroup['minPemenancyPeriodYear']=(!empty($employeeGroup['minPemenancyPeriod'])) ? intdiv($employeeGroup['minPemenancyPeriod'], 12) : null;
                $employeeGroup['minPemenancyPeriodMonth']= (!empty($employeeGroup['minPemenancyPeriod'])) ? fmod($employeeGroup['minPemenancyPeriod'], 12) : null;

                $leaveTypeRelateEmployeGroupsArr[] = $employeeGroup;
            }

            return $this->success(200, Lang::get('leaveEmployeeGroupMessages.basic.SUCC_GETALL'), $leaveTypeRelateEmployeGroupsArr);

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveEmployeeGroupMessages.basic.ERR_GET'), null);
        }

    }



    /**
     * Following function update the leave type employee group
     *
     * @param $groupData array containing the leave employee group data
     * @param $id leave type employee group id
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Leave Type Employee Group updated successfully!",
     * $data => null //$data has a similar set of values as the input
     *  */

    public function updateEmployeeGroup($id, $groupData)
    {
        try {
            if (!isset($groupData['name'])) {
                $data =[
                    'name'=> ["Required"]
                ];
                return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_REQUIRED'),  $data);
            }

            if (isset($groupData['name'])) {
                // check Employee Group Name exist for the same leave Type
                $checkEmployeeGroupExist =  $this->store->getFacade()::table('leaveEmployeeGroup')
                   ->where("leaveTypeId", $groupData['leaveTypeId'] )
                   ->where("name",$groupData['name'])
                   ->where('id', '!=', $id)
                   ->first();
                   Log::info([$checkEmployeeGroupExist,"lll"]);
                if (!empty($checkEmployeeGroupExist)) {
                    $data =[
                        'name'=> ["This is already existing."]
                    ];
                    return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_UNIQUE'),  $data);
                }
            }
            
            
            $existingLeaveType = $this->store->getById($this->leaveEmployeeGroupModel, $id);
            if (is_null($existingLeaveType)) {
                return $this->error(404, Lang::get('leaveEmployeeGroupMessages.basic.ERR_NOT_EXIST'), null);
            }


            $groupData['jobTitles']= (isset( $groupData['jobTitles'])) ? json_encode($groupData['jobTitles']) : json_encode([]);
            $groupData['employmentStatuses'] = (isset($groupData['employmentStatuses'])) ? json_encode($groupData['employmentStatuses']) : json_encode([]);
            $groupData['locations']= (isset( $groupData['locations'])) ? json_encode($groupData['locations']) : json_encode([]);
            $groupData['genders']= (isset( $groupData['genders'])) ? json_encode($groupData['genders']) : json_encode([]);
            
            if(isset( $groupData['name'])){
                $groupData['name']=$groupData['name'];

            }
            if(isset( $groupData['comment'])){
                $groupData['comment']= $groupData['comment'];

            }
            if(isset( $groupData['minPemenancyPeriod'])){
                $groupData['minPemenancyPeriod']= $groupData['minPemenancyPeriod'];

            }
            if(isset( $groupData['minServicePeriod'])){
                $groupData['minServicePeriod']= $groupData['minServicePeriod'];

            }
            
            $result = $this->store->updateById($this->leaveEmployeeGroupModel, $id, $groupData);

            if (!$result) {
                return $this->error(500, Lang::get('leaveEmployeeGroupMessages.basic.ERR_UPDATE'), $id);
            }
            
            return $this->success(200, Lang::get('leaveEmployeeGroupMessages.basic.SUCC_GETALL'), $result);
            
        } catch (Exception $e) {
            error_log($e->getMessage());
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveEmployeeGroupMessages.basic.ERR_GET'), null);
        }

    }

    /**
     * Following function delete a leave type employee group.
     *
     * @param $id leave type id
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Leave Type employee group deleted Successfully!",
     *      $data => {"title": "LK HR", ...}
     *
     */
    public function deleteLeaveEmployeeGroup($id)
    {
        try {
            
            $existingLeaveEmployeeGroup = $this->store->getById($this->leaveEmployeeGroupModel, $id);
            if (is_null($existingLeaveEmployeeGroup)) {
                return $this->error(404, Lang::get('leaveEmployeeGroupMessages.basic.ERR_NOT_EXIST'), null);
            }
            
            $leaveTypeAccrualRecordCount = DB::table('leaveAccrual')->where('leaveEmployeeGroupId', $id)->count();
            
            if ($leaveTypeAccrualRecordCount > 0)  {
                return $this->error(500, Lang::get('leaveEmployeeGroupMessages.basic.ERR_NOTALLOWED'), null);
            } 
            
            $result = $this->store->getFacade()::table('leaveEmployeeGroup')->where('id', $id)->update(['isDelete' => true]);
          
            if ($result==0) {
                return $this->error(500, Lang::get('leaveEmployeeGroupMessages.basic.ERR_DELETE'), $id);
            }

            return $this->success(200, Lang::get('leaveEmployeeGroupMessages.basic.SUCC_DELETE'), []);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_DELETE'), null);
        }
    }



    public function getLeaveTypeAccrualConfigsByLeaveTypeId($leaveTypeId, $accrualFrequency)
    {
        try {
            
            $relatedAccrualRecords = DB::table('leaveAccrual')->where('leaveTypeId', $leaveTypeId)->where('accrualFrequency', $accrualFrequency)->get();
            $accrualDataset = [];

            if($relatedAccrualRecords->count() > 0) {
                $accrualDataset = (array)$relatedAccrualRecords[0];
                unset($accrualDataset['leaveEmployeeGroupId']);
                unset($accrualDataset['amount']);
                unset($accrualDataset['id']);

                $accrualDataset['dayValue'] = null;

                $relatedEmployeeGroups = [];

                foreach ($relatedAccrualRecords as $key => $record) {
                    $record = (array) $record;
                    $groupRecord = [
                        'id' => $record['id'],
                        'employeeGroup' => $record['leaveEmployeeGroupId'],
                        'amount' => $record['amount']
                    ];
                    $relatedEmployeeGroups[] = $groupRecord;
                }

                $accrualDataset['relatedEmployeeGroups'] = $relatedEmployeeGroups;

                if (!empty($accrualDataset['dayOfCreditingForAnnualFrequency'])) {
                   $annualFrequencyDataArr = explode("-",$accrualDataset['dayOfCreditingForAnnualFrequency']);
                   
                    if (!empty($annualFrequencyDataArr[0])) {
                        $annualFrequencyArr =  str_split($annualFrequencyDataArr[0]);
                            if (sizeof($annualFrequencyArr) > 0) {
                                $accrualDataset['dayOfCreditingForAnnualFrequency'] = ($annualFrequencyArr[0] !== '0') ? (int) $annualFrequencyDataArr[0] : (int) $annualFrequencyArr[1];
                            } 
                    }

                    if (!empty($annualFrequencyDataArr[1])) {
                        $annualFrequencyDayValArr =  str_split($annualFrequencyDataArr[1]);
                            if (sizeof($annualFrequencyDayValArr) > 0) {
                                $accrualDataset['dayValue'] = ($annualFrequencyDayValArr[0] !== '0') ? (int) $annualFrequencyDataArr[1] : (int) $annualFrequencyDayValArr[1];
                            } 
                    }
                }
            }
            
            $accrualDataset = (!empty($accrualDataset)) ? $accrualDataset : null;

            return $this->success(200, Lang::get('leaveEmployeeGroupMessages.basic.SUCC_GETALL'), $accrualDataset);
            
        } catch (Exception $e) {
            error_log($e->getMessage());
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveEmployeeGroupMessages.basic.ERR_GET'), null);
        }

    }




    public function setLeaveTypeAccrualConfigs($id, $accualConfigData)
    {
        try {

            DB::beginTransaction();

            $leaveTypeRecord = DB::table('leaveType')->where('id', $id)->first();
            $leaveTypeRecord = (array)$leaveTypeRecord;
            
            if (empty($accualConfigData['relatedEmployeeGroups'])) {
                return $this->error(500, Lang::get('leaveEmployeeGroupMessages.basic.ERR_EMP_GRP_CANNOT_EMPTY'),null);
            }

            if (empty($accualConfigData['accrualFrequency'])) {
                $errData['accrualFrequency'][] = 'This is a mandatory field.';
                return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'),$errData);
            }

            if (empty($accualConfigData['accrualValidFrom'])) {
                $errData['accrualValidFrom'][] = 'This is a mandatory field.';
                return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'),$errData);
            }

            if ($accualConfigData['accrualFrequency'] == 'MONTHLY' && empty($accualConfigData['firstAccrualForMonthlyFrequency'])) {
                $errData['firstAccrualForMonthlyFrequency'][] = 'This is a mandatory field.';
                return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'),$errData);
            }

            if ($accualConfigData['accrualFrequency'] == 'ANNUAL' && empty($accualConfigData['firstAccrualForAnnualfrequency'])) {
                $errData['firstAccrualForAnnualfrequency'][] = 'This is a mandatory field.';
                return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'),$errData);
            }

            if ($leaveTypeRecord['leavePeriod'] == 'STANDARD') {
                if ($accualConfigData['accrualFrequency'] == 'ANNUAL' && empty($accualConfigData['dayOfCreditingForAnnualFrequency'])) {
                    $errData['dayOfCreditingForAnnualFrequency'][] = 'This is a mandatory field.';
                    return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'),$errData);
                }

                if ($accualConfigData['accrualFrequency'] == 'MONTHLY' && empty($accualConfigData['dayOfCreditingForMonthlyFrequency'])) {
                    $errData['dayOfCreditingForMonthlyFrequency'][] = 'This is a mandatory field.';
                    return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'),$errData);
                }
            }

            if ($leaveTypeRecord['leavePeriod'] == 'HIRE_DATE_BASED') {
                if ($accualConfigData['accrualFrequency'] == 'MONTHLY' && empty($accualConfigData['dayOfCreditingForMonthlyFrequency'])) {
                    $errData['dayOfCreditingForMonthlyFrequency'][] = 'This is a mandatory field.';
                    return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'),$errData);
                }
            }


            $oldSelectedIds = $accualConfigData['oldSelectedIds'];
            unset($accualConfigData['oldSelectedIds']);
            
            $relatedAccrualRecords = DB::table('leaveAccrual')->where('leaveTypeId', $id)->where('accrualFrequency',$accualConfigData['accrualFrequency'])->get(['id']);
            $relatedAccrualRecordsDbIds =[];
            if (!empty($relatedAccrualRecords)) {

                foreach ($relatedAccrualRecords as $key => $value) {
                    $value = (array) $value;
                    $relatedAccrualRecordsDbIds[] = $value['id'];
                }
    
                $deletedAccrualConfigs = array_values(array_diff($relatedAccrualRecordsDbIds,$oldSelectedIds));
    
                if (!empty($deletedAccrualConfigs)) {
                    $affectedConfigRows = DB::table('leaveAccrual')->whereIn('id', $deletedAccrualConfigs)->delete();
                }
            }


            $relatedEmployeeGroups = (array) $accualConfigData['relatedEmployeeGroups'];
            unset($accualConfigData['relatedEmployeeGroups']);

            foreach ($relatedEmployeeGroups as $key => $employeeGroup) {
                $accrualData = $accualConfigData;
                $employeeGroup = (array) $employeeGroup;

                if (!empty($employeeGroup['id'])) {
                    $accrualData['id'] = $employeeGroup['id'];
                } else {
                    $accrualData['id'] = 'new';
                }
                $accrualData['leaveEmployeeGroupId'] = $employeeGroup['employeeGroup'];
                $accrualData['amount'] = (float) $employeeGroup['amount'];

                if (!isset($accrualData['proRateMethodFirstAccrualForAnnualFrequency'])) {
                    $accrualData['proRateMethodFirstAccrualForAnnualFrequency'] = null;
                }


                if ($accrualData['id'] == 'new') {
                    //insert new record
                    $result = DB::table('leaveAccrual')->insertGetId($accrualData);
                } else {
                    //update exsiting record
                    $accrualId = $accrualData['id'];
                    $accrualData = (array) $accrualData;
                
                    $result = $this->store->getFacade()::table('leaveAccrual')->where('id', $accrualData['id'])->update($accrualData);
                }

                if (is_null($result)) {
                    DB::rollBack();
                    return $this->error(500, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'), null);
                }

            }

            DB::commit();
            return $this->success(200, Lang::get('leaveEmployeeGroupMessages.basic.SUCC_CREATE'), []);
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'), null);
        }

    }



    public function createLeaveAccrualConfig($accualConfigData)
    {
        try {

            DB::beginTransaction();

            $leaveTypeRecord = DB::table('leaveType')->where('id', $accualConfigData['leaveTypeId'])->first();
            $leaveTypeRecord = (array)$leaveTypeRecord;
            
            if (empty($accualConfigData['leaveEmployeeGroupId'])) {
                return $this->error(500, Lang::get('leaveEmployeeGroupMessages.basic.ERR_EMP_GRP_CANNOT_EMPTY'),null);
            }

            if (empty($accualConfigData['accrualFrequency'])) {
                $errData['accrualFrequency'][] = 'This is a mandatory field.';
                return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'),$errData);
            }

            if (empty($accualConfigData['accrualValidFrom'])) {
                $errData['accrualValidFrom'][] = 'This is a mandatory field.';
                return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'),$errData);
            }

            if ($accualConfigData['accrualFrequency'] == 'MONTHLY' && empty($accualConfigData['firstAccrualForMonthlyFrequency'])) {
                $errData['firstAccrualForMonthlyFrequency'][] = 'This is a mandatory field.';
                return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'),$errData);
            }

            if ($accualConfigData['accrualFrequency'] == 'ANNUAL' && empty($accualConfigData['firstAccrualForAnnualfrequency'])) {
                $errData['firstAccrualForAnnualfrequency'][] = 'This is a mandatory field.';
                return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'),$errData);
            }

            if ($leaveTypeRecord['leavePeriod'] == 'STANDARD') {
                if ($accualConfigData['accrualFrequency'] == 'ANNUAL' && empty($accualConfigData['dayOfCreditingForAnnualFrequency'])) {
                    $errData['dayOfCreditingForAnnualFrequency'][] = 'This is a mandatory field.';
                    return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'),$errData);
                }

                if ($accualConfigData['accrualFrequency'] == 'MONTHLY' && empty($accualConfigData['dayOfCreditingForMonthlyFrequency'])) {
                    $errData['dayOfCreditingForMonthlyFrequency'][] = 'This is a mandatory field.';
                    return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'),$errData);
                }
            }

            if ($leaveTypeRecord['leavePeriod'] == 'HIRE_DATE_BASED') {
                if ($accualConfigData['accrualFrequency'] == 'MONTHLY' && empty($accualConfigData['dayOfCreditingForMonthlyFrequency'])) {
                    $errData['dayOfCreditingForMonthlyFrequency'][] = 'This is a mandatory field.';
                    return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'),$errData);
                }
            }

            //check whether has any multiple accrue process has under the one leave employee group
            $leaveAccrues = DB::table('leaveAccrual')
                ->where('leaveTypeId', $accualConfigData['leaveTypeId'])
                ->where('leaveEmployeeGroupId',$accualConfigData['leaveEmployeeGroupId'] )
                ->where('accrualFrequency', $accualConfigData['accrualFrequency'] )->count();

            if ($leaveAccrues > 0) {
                DB::rollBack();
                return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_HAS_MULTIPLE_ACCRUE'), []);
            }

            $accrualData = $accualConfigData;

            $accrualData['amount'] = (float) $accrualData['amount'];

            if (!isset($accrualData['proRateMethodFirstAccrualForAnnualFrequency'])) {
                $accrualData['proRateMethodFirstAccrualForAnnualFrequency'] = null;
            }

            $result = DB::table('leaveAccrual')->insertGetId($accrualData);

            DB::commit();
            return $this->success(200, Lang::get('leaveEmployeeGroupMessages.basic.SUCC_CREATE'), []);
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'), null);
        }

    }


    public function updateLeaveAccrualConfig($id, $accualConfigData)
    {
        try {

            DB::beginTransaction();

            $leaveTypeRecord = DB::table('leaveType')->where('id', $accualConfigData['leaveTypeId'])->first();
            $leaveTypeRecord = (array)$leaveTypeRecord;
            
            if (empty($accualConfigData['leaveEmployeeGroupId'])) {
                return $this->error(500, Lang::get('leaveEmployeeGroupMessages.basic.ERR_EMP_GRP_CANNOT_EMPTY'),null);
            }

            if (empty($accualConfigData['accrualFrequency'])) {
                $errData['accrualFrequency'][] = 'This is a mandatory field.';
                return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'),$errData);
            }

            if (empty($accualConfigData['accrualValidFrom'])) {
                $errData['accrualValidFrom'][] = 'This is a mandatory field.';
                return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'),$errData);
            }

            if ($accualConfigData['accrualFrequency'] == 'MONTHLY' && empty($accualConfigData['firstAccrualForMonthlyFrequency'])) {
                $errData['firstAccrualForMonthlyFrequency'][] = 'This is a mandatory field.';
                return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'),$errData);
            }

            if ($accualConfigData['accrualFrequency'] == 'ANNUAL' && empty($accualConfigData['firstAccrualForAnnualfrequency'])) {
                $errData['firstAccrualForAnnualfrequency'][] = 'This is a mandatory field.';
                return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'),$errData);
            }

            if ($leaveTypeRecord['leavePeriod'] == 'STANDARD') {
                if ($accualConfigData['accrualFrequency'] == 'ANNUAL' && empty($accualConfigData['dayOfCreditingForAnnualFrequency'])) {
                    $errData['dayOfCreditingForAnnualFrequency'][] = 'This is a mandatory field.';
                    return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'),$errData);
                }

                if ($accualConfigData['accrualFrequency'] == 'MONTHLY' && empty($accualConfigData['dayOfCreditingForMonthlyFrequency'])) {
                    $errData['dayOfCreditingForMonthlyFrequency'][] = 'This is a mandatory field.';
                    return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'),$errData);
                }
            }

            if ($leaveTypeRecord['leavePeriod'] == 'HIRE_DATE_BASED') {
                if ($accualConfigData['accrualFrequency'] == 'MONTHLY' && empty($accualConfigData['dayOfCreditingForMonthlyFrequency'])) {
                    $errData['dayOfCreditingForMonthlyFrequency'][] = 'This is a mandatory field.';
                    return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'),$errData);
                }
            }

            //check whether has any multiple accrue process has under the one leave employee group
            $leaveAccrues = DB::table('leaveAccrual')
                ->where('leaveTypeId', $accualConfigData['leaveTypeId'])
                ->where('leaveEmployeeGroupId',$accualConfigData['leaveEmployeeGroupId'] )
                ->where('id','!=', $accualConfigData['id'])
                ->where('accrualFrequency', $accualConfigData['accrualFrequency'] )->count();

            if ($leaveAccrues > 0) {
                DB::rollBack();
                return $this->error(400, Lang::get('leaveEmployeeGroupMessages.basic.ERR_HAS_MULTIPLE_ACCRUE'), []);
            }


            $accrualData = $accualConfigData;

            $accrualData['amount'] = (float) $accrualData['amount'];

            if (!isset($accrualData['proRateMethodFirstAccrualForAnnualFrequency'])) {
                $accrualData['proRateMethodFirstAccrualForAnnualFrequency'] = null;
            }

            //update exsiting record
            $accrualId = $accrualData['id'];
            $accrualData = (array) $accrualData;
        
            $result = $this->store->getFacade()::table('leaveAccrual')->where('id', $accrualData['id'])->update($accrualData);

            DB::commit();
            return $this->success(200, Lang::get('leaveEmployeeGroupMessages.basic.SUCC_CREATE'), []);
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveEmployeeGroupMessages.basic.ERR_CREATE'), null);
        }

    }
}