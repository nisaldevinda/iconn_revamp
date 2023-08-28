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
use Carbon\Carbon;

/**
 * Name: SelfServiceLockService
 * Module Creator: Tharindu Darshana
 */
class SelfServiceLockService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $selfServiceDatePeriodModel;
    private $selfServiceLockModel;
 
    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->selfServiceDatePeriodModel = $this->getModel('selfServiceLockDatePeriods', true);
        $this->selfServiceLockModel = $this->getModel('selfServiceLock', true);
    }
    

    /**
     * Following function creates a Self Service Date Period Record.
     *
     * @param $Scheme array containing the Scheme data
     * @return int | String | array
     *
     * Usage:
     * $Scheme => ["name": "scheme1","description:"text ..."]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Scheme created Successuflly",
     * $data => {"name": "scheme1}//$data has a similar set of values as the input
     *  */

    public function createDatePeriods($selfServiceDatePeriod)
    {
        try {
          
            $validationResponse = ModelValidator::validate($this->selfServiceDatePeriodModel, $selfServiceDatePeriod, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('selfServiceLockPeriodMessages.basic.ERR_CREATE'), $validationResponse);
            }

            //check date conflict for from dates and to dates
            $periods = $this->store->getFacade()::table('selfServiceLockDatePeriods')
                        ->where('isDelete', false)->get();
            
            $hasFormDateConflicts = false;
            $hasToDateConflicts = false;
            $compareFromDate = Carbon::parse($selfServiceDatePeriod['fromDate']);
            $compareToDate = Carbon::parse($selfServiceDatePeriod['toDate']);

            foreach ($periods as $key => $period) {
                $period = (array) $period;
                $periodFromDate = Carbon::parse($period['fromDate']);
                $periodToDate = Carbon::parse($period['toDate']);

                if ($compareFromDate->between($periodFromDate,$periodToDate)) {
                    $hasFormDateConflicts = true;
                }

                if ($compareToDate->between($periodFromDate,$periodToDate)) {
                    $hasToDateConflicts = true;
                }


            }

            if ($hasFormDateConflicts || $hasToDateConflicts) {
                return $this->error(404, Lang::get('selfServiceLockPeriodMessages.basic.ERR_HAS_DATE_PERIOD_CONFLICTS'), null);
            }

            $dataSet = $this->store->insert($this->selfServiceDatePeriodModel, $selfServiceDatePeriod, true);

            return $this->success(201, Lang::get('selfServiceLockPeriodMessages.basic.SUCC_CREATE'), $dataSet);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('selfServiceLockPeriodMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives self service lock date periods.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "self service lock date periods retrived Successfully",
     *      $data => {{"id": 1...}, {"id": 2....}}
     * ]
     */
    public function getDatePeriods($permittedFields, $options)
    {
        try {
            $selfServiceLockDatePeriods = $this->store->getAll(
                $this->selfServiceDatePeriodModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );

            return $this->success(200, Lang::get('selfServiceLockPeriodMessages.basic.SUCC_ALL_RETRIVE'), $selfServiceLockDatePeriods);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('selfServiceLockPeriodMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives self service lock date periods.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "self service lock date periods retrived Successfully",
     *      $data => {{"id": 1...}, {"id": 2....}}
     * ]
     */
    public function getAllDatePeriods($permittedFields, $options)
    {
        try {
            $selfServiceLockDatePeriods = $this->store->getAll(
                $this->selfServiceDatePeriodModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );

            if (!empty($selfServiceLockDatePeriods) && isset($selfServiceLockDatePeriods['data'])) {
                foreach ($selfServiceLockDatePeriods['data'] as $key => $value) {
                    $value = (array) $value;
                    $selfServiceLockDatePeriods['data'][$key]->fromDateOrginal = (!empty($value['fromDate'])) ? $value['fromDate'] : null;
                    $selfServiceLockDatePeriods['data'][$key]->toDateOrginal = (!empty($value['fromDate'])) ? $value['toDate'] : null;
                    $selfServiceLockDatePeriods['data'][$key]->fromDate = (!empty($value['fromDate'])) ? Carbon::parse($value['fromDate'])->format('d-m-Y') : null;
                    $selfServiceLockDatePeriods['data'][$key]->toDate = (!empty($value['toDate'])) ? Carbon::parse($value['toDate'])->format('d-m-Y') : null;
                }
            } elseif (!empty($selfServiceLockDatePeriods) && !isset($selfServiceLockDatePeriods['data'])) {
                foreach ($selfServiceLockDatePeriods as $key => $value) {
                    $value = (array) $value;
                    $selfServiceLockDatePeriods[$key]->fromDateOrginal = (!empty($value['fromDate'])) ? $value['fromDate'] : null;
                    $selfServiceLockDatePeriods[$key]->toDateOrginal = (!empty($value['fromDate'])) ? $value['toDate'] : null;
                    $selfServiceLockDatePeriods[$key]->fromDate = (!empty($value['fromDate'])) ? Carbon::parse($value['fromDate'])->format('d-m-Y') : null;
                    $selfServiceLockDatePeriods[$key]->toDate = (!empty($value['toDate'])) ? Carbon::parse($value['toDate'])->format('d-m-Y') : null;
                }
            }


            return $this->success(200, Lang::get('selfServiceLockPeriodMessages.basic.SUCC_ALL_RETRIVE'), $selfServiceLockDatePeriods);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('selfServiceLockPeriodMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single self service lock date period for a provided id.
     *
     * @param $id self service date period id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "self service lock date period retrived Successfully",
     *      $data => {"id": 1.....}
     * ]
     */
    public function getDatePeriod($id)
    {
        try {
            $datePeriod = $this->store->getFacade()::table('selfServiceLockDatePeriods')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($datePeriod)) {
                return $this->error(404, Lang::get('selfServiceLockPeriodMessages.basic.ERR_NONEXISTENT'), null);
            }

            return $this->success(200, Lang::get('selfServiceLockPeriodMessages.basic.SUCC_SINGLE_RETRIVE'), $datePeriod);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('selfServiceLockPeriodMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /**
     * Following function updates a Scheme.
     *
     * @param $id self service date period id
     * @param $periodData array containing self service lock date period data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Self service date period updated Successfully.",
     *      $data => {"id": 1, name": "scheme"} // has a similar set of data as entered to updating Scheme.
     *
     */
    public function updateDatePeriods($id, $periodData)
    {
        try {
            $periodData['id'] = $id; 
            $validationResponse = ModelValidator::validate($this->selfServiceDatePeriodModel, $periodData, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('selfServiceLockPeriodMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $periodRecord = $this->store->getFacade()::table('selfServiceLockDatePeriods')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($periodRecord)) {
                return $this->error(404, Lang::get('selfServiceLockPeriodMessages.basic.ERR_NONEXISTENT'), null);
            }

            //check date conflict for from dates and to dates
            $periods = $this->store->getFacade()::table('selfServiceLockDatePeriods')
                        ->where('id', '!=',$id)
                        ->where('isDelete', false)->get();
            
            $hasFormDateConflicts = false;
            $hasToDateConflicts = false;
            $compareFromDate = Carbon::parse($periodData['fromDate']);
            $compareToDate = Carbon::parse($periodData['toDate']);

            foreach ($periods as $key => $period) {
                $period = (array) $period;
                $periodFromDate = Carbon::parse($period['fromDate']);
                $periodToDate = Carbon::parse($period['toDate']);

                if ($compareFromDate->between($periodFromDate,$periodToDate)) {
                    $hasFormDateConflicts = true;
                }

                if ($compareToDate->between($periodFromDate,$periodToDate)) {
                    $hasToDateConflicts = true;
                }


            }

            if ($hasFormDateConflicts || $hasToDateConflicts) {
                return $this->error(404, Lang::get('selfServiceLockPeriodMessages.basic.ERR_HAS_DATE_PERIOD_CONFLICTS'), null);
            }
            
            $result = $this->store->updateById($this->selfServiceDatePeriodModel, $id, $periodData);

            return $this->success(200, Lang::get('selfServiceLockPeriodMessages.basic.SUCC_UPDATE'), $periodData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('selfServiceLockPeriodMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id self service date period id
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "self service date period deleted successfully.",
     *      $data => null
     *
     */
    public function deleteDatePeriods($id)
    {
        try {
            $datePeriod = $this->store->getById($this->selfServiceDatePeriodModel, $id);
            if (is_null($datePeriod)) {
                return $this->error(404, Lang::get('selfServiceLockPeriodMessages.basic.ERR_NONEXISTENT'), null);
            }
            
            $getRelatedLinkedConfigs = $this->store->getFacade()::table('selfServiceLockConfigs')
                    ->where('selfServiceLockDatePeriodId',$id)
                    ->where('isDelete', false)->get();

            //check whether self service lock date period is already used with self service lock configuration
            if (sizeOf($getRelatedLinkedConfigs) > 0) {
                return $this->error(502, Lang::get('selfServiceLockPeriodMessages.basic.ERR_NOTALLOWED'), null );
            } 

            $this->store->getFacade()::table('selfServiceLockDatePeriods')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('selfServiceLockPeriodMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('selfServiceLockPeriodMessages.basic.ERR_DELETE'), null);
        }
    }


    /**
     * Following function creates a Self Service Lock Config Record.
     *
     * @param $slefServiceLockConfigData array containing the self service lock config data
     * @return int | String | array
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Self service lock config created Successuflly",
     * $data => {"name": "scheme1}//$data has a similar set of values as the input
     *  */

    public function createSelfServiceLockConfig($slefServiceLockConfigData)
    {
        try {

            $slefServiceLockConfigData['status'] = 'LOCKED';
            $slefServiceLockConfigData['selfServicesStatus'] = json_encode($slefServiceLockConfigData['selfServicesStatus']);
          
            $validationResponse = ModelValidator::validate($this->selfServiceLockModel, $slefServiceLockConfigData, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('selfServiceLockPeriodMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $serviceLockConfig = $this->store->insert($this->selfServiceLockModel, $slefServiceLockConfigData, true);

            return $this->success(201, Lang::get('selfServiceLockPeriodMessages.basic.SUCC_CREATE'), $serviceLockConfig);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('selfServiceLockPeriodMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all Self Service Lock Config Records.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All retrive Successfully",
     *      $data => {{"id": 1.....}, {"id": 1......}}
     * ]
     */
    public function getAllSelfServiceLockConfigs($permittedFields, $options)
    {
        try {

            $selfServiceLockDatePeriodId = $options['filterBy'];
            $customWhereClauses = isset($options['filterBy']) && !empty($options['filterBy']) ? [['isDelete','=',false],['selfServiceLockDatePeriodId', '=',  $selfServiceLockDatePeriodId]] : [['isDelete','=',false]];

            $selfServiceLockConfigs = $this->store->getAll(
                $this->selfServiceLockModel,
                $permittedFields,
                $options,
                ['selfServiceLockDatePeriod'],
                $customWhereClauses
            );

            if (isset($selfServiceLockConfigs['data'])) {
                foreach ($selfServiceLockConfigs['data'] as $key => $value) {
                    $value = (array) $value;
                    $selfServiceLockConfigs['data'][$key]->effectiveFromLabel = (!empty($value['effectiveFrom'])) ? $value['effectiveFrom'] : null;
                    $selfServiceLockConfigs['data'][$key]->effectiveFrom = (!empty($value['effectiveFrom'])) ? Carbon::parse($value['effectiveFrom'])->format('d-m-Y') : null;
                    $selfServiceLockConfigs['data'][$key]->selfServiceLockDatePeriod->fromDate = (!empty($value['selfServiceLockDatePeriod']->fromDate)) ? Carbon::parse($value['selfServiceLockDatePeriod']->fromDate)->format('d-m-Y') : null;
                    $selfServiceLockConfigs['data'][$key]->selfServiceLockDatePeriod->toDate = (!empty($value['selfServiceLockDatePeriod']->toDate)) ? Carbon::parse($value['selfServiceLockDatePeriod']->toDate)->format('d-m-Y') : null;
                }
            }
            return $this->success(200, Lang::get('selfServiceLockPeriodMessages.basic.SUCC_ALL_RETRIVE'), $selfServiceLockConfigs);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('selfServiceLockPeriodMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single Self Service lock config for a provided id.
     *
     * @param $id  id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "self servic lock record retrived Successfully",
     *      $data => {"id": 1....}
     * ]
     */
    public function getSelfServiceLockConfig($id)
    {
        try {
            $scheme = $this->store->getFacade()::table('Scheme')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($scheme)) {
                return $this->error(404, Lang::get('selfServiceLockPeriodMessages.basic.ERR_NONEXISTENT'), null);
            }

            return $this->success(200, Lang::get('selfServiceLockPeriodMessages.basic.SUCC_SINGLE_RETRIVE'), $scheme);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('selfServiceLockPeriodMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /**
     * Following function updates a Self service lock config record.
     *
     * @param $id Self service lock record id
     * @param $selfLockData array containing Self service lock data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Scheme updated Successfully.",
     *      $data => {"id": 1.....} // has a similar set of data as entered to updating Scheme.
     *
     */
    public function updateSelfServiceLockConfig($id, $selfLockData)
    {
        try {
            $selfLockData['id'] = $id; 
            $selfLockData['selfServicesStatus'] = json_encode($selfLockData['selfServicesStatus']);
            $validationResponse = ModelValidator::validate($this->selfServiceLockModel, $selfLockData, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('selfServiceLockPeriodMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $selfServiceLockRecord = $this->store->getFacade()::table('selfServiceLockConfigs')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($selfServiceLockRecord)) {
                return $this->error(404, Lang::get('selfServiceLockPeriodMessages.basic.ERR_NONEXISTENT'), null);
            }

            $result = $this->store->updateById($this->selfServiceLockModel, $id, $selfLockData);

            return $this->success(200, Lang::get('selfServiceLockPeriodMessages.basic.SUCC_UPDATE'), $selfLockData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('selfServiceLockPeriodMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id self service lock record id
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Self service lock config record deleted successfully.",
     *      $data => null
     *
     */
    public function deleteSelfServiceLockConfig($id)
    {
        try {
            $scheme = $this->store->getById($this->selfServiceLockModel, $id);
            if (is_null($scheme)) {
                return $this->error(404, Lang::get('selfServiceLockPeriodMessages.basic.ERR_NONEXISTENT'), null);
            }
            $recordExist = Util::checkRecordsExist($this->selfServiceLockModel,$id);
            if (!empty($recordExist) ) {
                return $this->error(502, Lang::get('selfServiceLockPeriodMessages.basic.ERR_NOTALLOWED'), null );
            } 
            $this->store->getFacade()::table('selfServiceLockDatePeriods')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('selfServiceLockPeriodMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('selfServiceLockPeriodMessages.basic.ERR_DELETE'), null);
        }
    }
  
}
