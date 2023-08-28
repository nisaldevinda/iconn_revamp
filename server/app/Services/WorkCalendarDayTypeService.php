<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Traits\JsonModelReader;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Name: Work Calendar Day Type Service
 * Purpose: Performs tasks related to the Work Calendar Day Type model.
 * Description:  Work Calendar Day Type Service class is called by the  WorkCalendarDayTypeController 
 * where the requests related code logics are processed
 * Module Creator: Tharindu Darshana
 */

class WorkCalendarDayTypeService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $workCalendarTableName;
    private $dateNamesTableName;
    private $dateTypesTableName;
    private $specialDaysTableName;
    private $workCalendarDayTypeModel;
    private $baseDayTypeModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->workCalendarDayTypeModel = $this->getModel('workCalendarDayType', true);
        $this->baseDayTypeModel = $this->getModel('baseDayType', true);
    }

    /**
     * Following function creates a new work calendar day type.
     *
     * @param $dayTypeData array containing the work calendar day type data
     * @return int | String | array | object 
     *
     * Usage:
     * $dayTypeData => ["name": "Working Day"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "date type created Successuflly",
     * $data => {"name": "Working Day"}//$data has a similar set of values as the input
     *  
     * */
    public function createWorkCalendarDayType($dayTypeData)
    {
        try {
            if (!empty($dayTypeData['shortCode'])) {
                $duplicateCodeCount = DB::table('workCalendarDayType')->where('shortCode', $dayTypeData['shortCode'])->where('isDelete', false)->count();
                
                if ($duplicateCodeCount > 0) {
                    $errData['shortCode'] = ['This is an unique field.'];
                    return $this->error(500, Lang::get('workCalendarDayTypeMessages.basic.ERR_CREATE'), $errData);
                }
            
            }

            if (!empty($dayTypeData['typeColor'])) {
                $duplicateCodeCount = DB::table('workCalendarDayType')->where('typeColor', $dayTypeData['typeColor'])->where('isDelete', false)->count();
                
                if ($duplicateCodeCount > 0) {
                    $errData['typeColor'] = ['This is an unique field.'];
                    return $this->error(500, Lang::get('workCalendarDayTypeMessages.basic.ERR_CREATE'), $errData);
                }
            
            }

            if (!empty($dayTypeData['name'])) {
                $duplicateCodeCount = DB::table('workCalendarDayType')->where('name', $dayTypeData['name'])->where('isDelete', false)->count();
                
                if ($duplicateCodeCount > 0) {
                    $errData['name'] = ['This is an unique field.'];
                    return $this->error(500, Lang::get('workCalendarDayTypeMessages.basic.ERR_UPDATE'), $errData);
                }
            
            }

            $dayTypeId =  $this->store->insert($this->workCalendarDayTypeModel, [
                'name' => $dayTypeData['name'],
                'shortCode' => $dayTypeData['shortCode'],
                'typeColor' => $dayTypeData['typeColor'],
                'baseDayTypeId' => $dayTypeData['baseDayTypeId']
            ], true);                       ;
        
            return $this->success(201, Lang::get('workCalendarDayTypeMessages.basic.SUCC_CREATE'), []);
               
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workCalendarDayTypeMessages.basic.ERR_CREATE'), null);
        }
    }

   

    /**
     * Following function can be used to fetch a calendar day type list.
     * 
     * @return int | String | array | object 
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Calendar day type list loaded successfully",
     * $data => [
     * {
     * "id": 0,
     * "name": "Working Day",
     * "shortCode": "WD",
     * "typeColor": "#E5E5E5"
     * } 
     * ]
     *  
     * */
    public function getDayTypeList($permittedFields, $options)
    {
        try {
            $filteredData = $this->store->getAll(
                $this->workCalendarDayTypeModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('workCalendarDayTypeMessages.basic.SUCC_ALL_RETRIVE'), $filteredData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workCalendarDayTypeMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }


    /**
     * Following function can be used to fetch base day type list.
     * 
     * @return int | String | array | object 
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Calendar day type list loaded successfully",
     * $data => [
     * {
     * "id": 0,
     * "name": "Working Day",
     * } 
     * ]
     *  
     * */
    public function getAllBaseDayTypeList($permittedFields, $options)
    {
        try {
            $filteredData = $this->store->getAll(
                $this->baseDayTypeModel,
                $permittedFields,
                $options,
                [],
                [['isActive','=',true]]
            );
            return $this->success(200, Lang::get('workCalendarDayTypeMessages.basic.SUCC_ALL_RETRIVE'), $filteredData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workCalendarDayTypeMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

   
    /**
     * Following function can be used to update work calendar day type.
     * @param $id number containing the workCalendarDayTypeId
     *
     * Sample output: [
     *      $statusCode => 200,
     *      $message => "work calendar day type updated successfully.",
     *      $data => null
     * }]
     * 
     * */
    public function updateCaledarDayTypeData($id, $dayTypeData)
    {
        try {

            $oldData = DB::table('workCalendarDayType')->where('id', $id)->first();

            if (empty($oldData)) {
                return $this->error(500, Lang::get('workCalendarDayTypeMessages.basic.ERR_NONEXIST_CAL_DAY_TYPE'), $errData);
            }

            if (!empty($dayTypeData['shortCode'])) {
                $duplicateCodeCount = DB::table('workCalendarDayType')->where('shortCode', $dayTypeData['shortCode'])->where('id','!=', $id)->where('isDelete', false)->count();
                
                if ($duplicateCodeCount > 0) {
                    $errData['shortCode'] = ['This is an unique field.'];
                    return $this->error(500, Lang::get('workCalendarDayTypeMessages.basic.ERR_UPDATE'), $errData);
                }
            
            }

            if (!empty($dayTypeData['typeColor'])) {
                $duplicateCodeCount = DB::table('workCalendarDayType')->where('typeColor', $dayTypeData['typeColor'])->where('id','!=', $id)->where('isDelete', false)->count();
                
                if ($duplicateCodeCount > 0) {
                    $errData['typeColor'] = ['This is an unique field.'];
                    return $this->error(500, Lang::get('workCalendarDayTypeMessages.basic.ERR_UPDATE'), $errData);
                }
            
            }

            if (!empty($dayTypeData['name'])) {
                $duplicateCodeCount = DB::table('workCalendarDayType')->where('name', $dayTypeData['name'])->where('id','!=', $id)->where('isDelete', false)->count();
                
                if ($duplicateCodeCount > 0) {
                    $errData['name'] = ['This is an unique field.'];
                    return $this->error(500, Lang::get('workCalendarDayTypeMessages.basic.ERR_UPDATE'), $errData);
                }
            
            }

            $dateId = $this->store->updateById($this->workCalendarDayTypeModel, $dayTypeData["id"], ['shortCode' =>$dayTypeData['shortCode'], 'name' => $dayTypeData['name'], 'typeColor' => $dayTypeData['typeColor'], 'baseDayTypeId' => $dayTypeData['baseDayTypeId']]);

            return $this->success(201, Lang::get('workCalendarDayTypeMessages.basic.SUCC_UPDATE'), []);
        } catch (Exception $e) {
            return $this->success(201, Lang::get('workCalendarDayTypeMessages.basic.ERR_UPDATE'), []);
            Log::error($e->getMessage());
            return [];
        }
    }

    /**
     * Following function sets the isDelete to true.
     *
     * @param $id workCalendarDayType id
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "work calendar day type deleted successfully.",
     *      $data => null
     *
     */
    public function deleteWorkCalendarDayType($id)
    {
        try {

            $dbDayType = DB::table('workCalendarDayType')->where('id', $id)->first();
            if (is_null($dbDayType)) {
                return $this->error(500, Lang::get('workCalendarDayTypeMessages.basic.ERR_NONEXIST_CAL_DAY_TYPE'), []);
            }

            //get records that link with this day type
            $workCalendarDateNamesRecordCount = DB::table('workCalendarDateNames')->where('workCalendarDayTypeId', $id)->count();
            $workCalendarSpecialDaysRecordCount = DB::table('workCalendarSpecialDays')->where('workCalendarDayTypeId', $id)->count();
            $attendanceSummeryRecordCount = DB::table('attendance_summary')->where('dayTypeId', $id)->count();
            $leaveTypeWorkingDayTypesRecordCount = DB::table('leaveTypeWorkingDayTypes')->where('dayTypeId', $id)->count();

            if ($workCalendarDateNamesRecordCount > 0 || $workCalendarSpecialDaysRecordCount > 0 || $attendanceSummeryRecordCount > 0 || $leaveTypeWorkingDayTypesRecordCount > 0) {
                return $this->error(500, Lang::get('workCalendarDayTypeMessages.basic.ERR_DAY_TYPE_USED'), []);
            }

            $this->store->getFacade()::table('workCalendarDayType')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('workCalendarDayTypeMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workCalendarDayTypeMessages.basic.ERR_DELETE'), null);
        }
    }
}
