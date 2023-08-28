<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Traits\JsonModelReader;
use Illuminate\Support\Facades\DB;
use App\Traits\ConfigHelper;
use Carbon\Carbon;

/**
 * Name: Work Shift Pay Configuration Service
 * Purpose: Performs tasks related to the shift based Pay confiurations.
 * Module Creator: Tharindu Darshana
 */

class WorkShiftPayCofigurationService extends BaseService
{
    use JsonModelReader;
    use ConfigHelper;

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
     * Following function can be used to set work shift based pay configuration
     * @param $id number containing the work shift id
     *
     * 
     * */
    public function setPayConfigurations($id, $payConfigData)
    {
        try {
            DB::beginTransaction();

            $dataSet = json_decode($payConfigData['payConfigData']);
            $workShiftId = $id;
            $oldDayTypeConfigDataIds = [];
            $oldDayTypeConfigData = $this->store->getFacade()::table('workShiftPayConfiguration')
                        ->where('workShiftId', $workShiftId)
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
                    'workCalendarDayTypeId' => $value['dayTypeId']
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
                                'thresholdType' => $threshold['thresholdType']
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
                            'thresholdType' => $threshold['thresholdType']
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

            DB::commit();
            return $this->success(201, Lang::get('workShiftMessages.basic.SUCC_SET_PAY_CONFIG'), []);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workShiftMessages.basic.ERR_SET_PAY_CONFIG'), null);
            
        }
    }

    /**
     * Following function can be used to get time base pay configurations maintain state
     * 
     * */
    public function getTimeBasePayConfigState() {
        try {
            $isMaintainTimeBasePayConfig = $this->getConfigValue('maintain_time_base_pay_configuration');
            $dataSet = [
                'isMaintainTimeBasePayConfig' => $isMaintainTimeBasePayConfig
            ];
            
            return $this->success(201, Lang::get('workShiftMessages.basic.SUCC_GET_PAY_CONFIG'), $dataSet);
        } catch (Exception $e) {
            return $this->success($e->getCode(), Lang::get('workShiftMessages.basic.ERR_GET_PAY_CONFIG'), []);
            Log::error($e->getMessage());
            return [];
        }

    }
    public function getPayConfiguration($id) {
        try {

            $workShiftRealteDayTypes = $this->store->getFacade()::table('workShiftPayConfiguration')
                ->leftJoin('workCalendarDayType', 'workCalendarDayType.id', '=', 'workShiftPayConfiguration.workCalendarDayTypeId')
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

                    // error_log(json_encode($relatedThresholdData));
                    $thresholdArr = [];
                    foreach ($relatedThresholdData as $key => $threshold) {
                        $threshold = (array) $threshold;
                        $tempThesholdData = [
                            "id" => $threshold['id'],
                            "hoursPerDay" => $threshold['hoursPerDay'],
                            "payTypeId" => $threshold['payTypeId'],
                            "thresholdKey" => $threshold['thresholdSequence'],
                            "thresholdType" => $threshold['thresholdType'],
                            "showAddBtn" => false
                            
                        ];
                        $thresholdArr[] = $tempThesholdData;

                        //set disable pay type
                        $payKey = 'key'.$threshold['payTypeId'];
                        $dayTypeWisePayType[$payKey]['disabled'] = true;
                    }
                    if (sizeof($thresholdArr) == 3) {
                        $thresholdArr[0]['showAddBtn'] = false;
                        $thresholdArr[1]['showAddBtn'] = false;
                        $thresholdArr[2]['showAddBtn'] = false;
                    } elseif(sizeof($thresholdArr) == 2) {
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
            $dataSet = [
                'data' => $processedDatset,
                'selectedOptionArr' => $selectedOptionArr
            ];
            


            return $this->success(201, Lang::get('workShiftMessages.basic.SUCC_GET_PAY_CONFIG'), $dataSet);
        } catch (Exception $e) {
            return $this->success($e->getCode(), Lang::get('workShiftMessages.basic.ERR_GET_PAY_CONFIG'), []);
            Log::error($e->getMessage());
            return [];
        }
    }

    
}
