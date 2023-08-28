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
 * Name: Pay Type Service
 * Purpose: Performs tasks related to the Pay Type model.
 * Description:  Pay Type Service class is called by the  PayTypeController 
 * where the requests related code logics are processed
 * Module Creator: Tharindu Darshana
 */

class PayTypeService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $workCalendarTableName;
    private $dateNamesTableName;
    private $dateTypesTableName;
    private $specialDaysTableName;
    private $payTypeModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->payTypeModel = $this->getModel('payType', true);
    }

    /**
     * Following function creates a new work calendar day type.
     *
     * @param $payTypeData array containing the work calendar day type data
     * @return int | String | array | object 
     *
     * Usage:
     * $payTypeData => ["name": "Working Day"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "date type created Successuflly",
     * $data => {"name": "Working Day"}//$data has a similar set of values as the input
     *  
     * */
    public function createPayType($payTypeData)
    {
        try {
            if (!empty($payTypeData['code'])) {
                $duplicateCodeCount = DB::table('payType')->where('code', $payTypeData['code'])->count();
                error_log(json_encode($duplicateCodeCount));
                
                if ($duplicateCodeCount > 0) {
                    $errData['code'] = ['This is an unique field.'];
                    return $this->error(500, Lang::get('payTypeMessages.basic.ERR_CREATE'), $errData);
                }
            
            }

            if ($payTypeData['type'] == 'GENERAL') {
                $payTypeData['rate'] = 0;
            }

            $dayTypeId = DB::table('payType')->insertGetId([
                'name' => $payTypeData['name'],
                'code' => $payTypeData['code'],
                'type' => $payTypeData['type'],
                'rate' => $payTypeData['rate'],
            ]);
        
            return $this->success(201, Lang::get('payTypeMessages.basic.SUCC_CREATE'), $dayTypeId);
               
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('payTypeMessages.basic.ERR_CREATE'), null);
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
    public function getPayTypeList($permittedFields, $options)
    {
        try {
            $filteredData = $this->store->getAll(
                $this->payTypeModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('payTypeMessages.basic.SUCC_ALL_RETRIVE'), $filteredData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('payTypeMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrive ot pay type list
     * 
     * @return int | String | array | object 
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Ot pay types retrive successfully",
     * $data => [
     * {
     * "id": 1,
     * "name": "Single OT",
     * "code": "SOT",
     * } 
     * ]
     *  
     * */
    public function getOTPayTypeList($permittedFields, $options)
    {
        try {
            $filteredData = DB::table('payType')
            ->where('type', 'OVERTIME')
            ->where('isDelete', false)->get();

            return $this->success(200, Lang::get('payTypeMessages.basic.SUCC_ALL_RETRIVE'), $filteredData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('payTypeMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

   
    /**
     * Following function can be used to update work calendar day type.
     * @param $id number containing the payTypeId
     *
     * Sample output: [
     *      $statusCode => 200,
     *      $message => "work calendar day type updated successfully.",
     *      $data => null
     * }]
     * 
     * */
    public function updatePayTypeData($id, $payTypeData)
    {
        try {

            $oldData = DB::table('payType')->where('id', $id)->first();

            if (empty($oldData)) {
                return $this->error(500, Lang::get('payTypeMessages.basic.ERR_NONEXIST_CAL_DAY_TYPE'), $errData);
            }

            if (!empty($payTypeData['code'])) {
                $duplicateCodeCount = DB::table('payType')->where('code', $payTypeData['code'])->where('id','!=', $id)->count();
                
                if ($duplicateCodeCount > 0) {
                    $errData['code'] = ['This is an unique field.'];
                    return $this->error(500, Lang::get('payTypeMessages.basic.ERR_UPDATE'), $errData);
                }
            
            }

            if ($payTypeData['type'] == 'GENERAL') {
                $payTypeData['rate'] = 0;
            }


            $dateId = DB::table('payType')->where('id', $id)
                ->update(['code' =>$payTypeData['code'], 'name' => $payTypeData['name'], 'type' => $payTypeData['type'], 'rate' => $payTypeData['rate']]);

            return $this->success(201, Lang::get('payTypeMessages.basic.SUCC_UPDATE'), []);
        } catch (Exception $e) {
            return $this->success(201, Lang::get('payTypeMessages.basic.ERR_UPDATE'), []);
            Log::error($e->getMessage());
            return [];
        }
    }

    /**
     * Following function sets the isDelete to true.
     *
     * @param $id payType id
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "pay type deleted successfully.",
     *      $data => null
     *
     */
    public function deletePayType($id)
    {
        try {

            $oldData = DB::table('payType')->where('id', $id)->first();
            if (empty($oldData)) {
                return $this->error(404, Lang::get('payTypeMessages.basic.ERR_NONEXIST_CAL_DAY_TYPE'), null);
            }

            $relatedThresholdsCount =  DB::table('workShiftPayConfigurationThreshold')->where('payTypeId', $id)->count();
            if ($relatedThresholdsCount > 0) {
                return $this->error(400, Lang::get('payTypeMessages.basic.ERR_NOTALLOWED'), null);
            }

            $this->store->getFacade()::table('payType')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('payTypeMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('payTypeMessages.basic.ERR_DELETE'), null);
        }
    }
}
