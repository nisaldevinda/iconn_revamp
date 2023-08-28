<?php

namespace App\Services;

use App\Library\Interfaces\ModelReaderInterface;
use Log;
use \Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\DB;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Traits\JsonModelReader;
use Carbon\Carbon;
/**
 * Name: FinancialYearService
 * Purpose: Performs tasks related to the FinancialYear model.
 * Description: WorkflowApproverPool Service class is called by the FinancialYearController where the requests related
 * to FinancialYear Model (basic operations and others). Table that is being modified is FinancialYear.
 * Module Creator: Tharindu Darshana
 */
class FinancialYearService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $financialYearModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->financialYearModel = $this->getModel('financialYear', true);
    }
    

    /**
     * Following function creates a financial year.
     * 
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "financial year created Successuflly",
     * $data => {"name": "Group 1"}//$data has a similar set of values as the input
     *  */

    public function createFinancialYear($financialYearData)
    {
        try {
            $validationResponse = ModelValidator::validate($this->financialYearModel, $financialYearData, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('financialYearMessages.basic.ERR_CREATE'), $validationResponse);
            }

            //check date range over lappinf
            $allFinancialYears = $this->store->getFacade()::table('financialYear')->where('isDelete', false)->get();
            $recFormDate = Carbon::parse($financialYearData['fromYearAndMonth']);
            $recToDate = Carbon::parse($financialYearData['toYearAndMonth']);

            $hasConflict = false;
            foreach ($allFinancialYears as $key => $dateRec) {
                $dateRec = (array) $dateRec;
                $fromDate = Carbon::parse($dateRec['fromYearAndMonth']);
                $toDate = Carbon::parse($dateRec['toYearAndMonth']);

                if ($recFormDate->between($fromDate, $toDate) || $recToDate->between($fromDate, $toDate)) {
                    $hasConflict = true;
                }
            }

            if ($hasConflict) {
                return $this->error(500, Lang::get('financialYearMessages.basic.ERR_NOTALLOWED_CREATE_HAS_CONFLICTS'), null);
            }

            $newfinancialYear = $this->store->insert($this->financialYearModel, $financialYearData, true);

            return $this->success(201, Lang::get('financialYearMessages.basic.SUCC_CREATE'), $newfinancialYear);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_CREATE'), null);
        }
    }

    /**
     * Following function retrives a single financial year provided id.
     *
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Financial year retrieved Successfully!",
     *      $data => {"title": "LK HR", ...}
     * ]
     */
    public function getFinancialYear($id)
    {
        try {
            $financialYear = $this->store->getById($this->financialYearModel, $id);
            return $this->success(200, Lang::get('financialYearMessages.basic.SUCC_GET'), $financialYear);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('financialYearMessages.basic.ERR_GET'), null);
        }
    }


    /** 
     * Following function retrives all Financial years.
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Financial years retrived Successuflly",
     *      $data => {{"id": 1, poolName": "Pool 1"}, {"id": 1, poolName": "Pool 2"}}
     * ] 
     */
    public function getAllFinancialYears($permittedFields, $options)
    {
        try {
            $filteredWorkflowApproverPools = $this->store->getAll(
                $this->financialYearModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]);

            if (!empty($filteredWorkflowApproverPools['data'])) {
                foreach ($filteredWorkflowApproverPools['data'] as $key => $value) {
                    $value = (array) $value;
                    $filteredWorkflowApproverPools['data'][$key]->isSetAsDefault = ($value['isSetAsDefault']) ? 'Yes' : 'No';
                }
            }

            return $this->success(200, Lang::get('financialYearMessages.basic.SUCC_ALL_RETRIVE'), $filteredWorkflowApproverPools);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    

    /**
     * Following function updates a financial year.
     * 
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "Financial year updated successfully.",
     *      $data => {"id": 1, actionName": "Relative"} // has a similar set of data as entered to updating Workflow Approver Pools.
     * 
     */
    public function updateFinancialYear($id, $financialYearData)
    {
        try {
               
            $validationResponse = ModelValidator::validate($this->financialYearModel, $financialYearData, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('financialYearMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbWorkflowApproverPool = $this->store->getById($this->financialYearModel, $id);
            if (is_null($dbWorkflowApproverPool)) {
                return $this->error(404, Lang::get('financialYearMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            //check date range over lappinf
            $allFinancialYears = $this->store->getFacade()::table('financialYear')->where('isDelete', false)->where('id','!=',$id)->get();
            $recFormDate = Carbon::parse($financialYearData['fromYearAndMonth']);
            $recToDate = Carbon::parse($financialYearData['toYearAndMonth']);

            $hasConflict = false;
            foreach ($allFinancialYears as $key => $dateRec) {
                $dateRec = (array) $dateRec;
                $fromDate = Carbon::parse($dateRec['fromYearAndMonth']);
                $toDate = Carbon::parse($dateRec['toYearAndMonth']);

                if ($recFormDate->between($fromDate, $toDate) || $recToDate->between($fromDate, $toDate)) {
                    $hasConflict = true;
                }
            }

            if ($hasConflict) {
                return $this->error(502, Lang::get('financialYearMessages.basic.ERR_NOTALLOWED_UPDATE_HAS_CONFLICTS'), null);
            }

            $result = $this->store->updateById($this->financialYearModel, $id, $financialYearData);

            if (!$result) {
                return $this->error(502, Lang::get('financialYearMessages.basic.ERR_UPDATE'), $id);
            }

            if ($financialYearData['isSetAsDefault']) {
                $updateOtherYearsDefaultState = $this->store->getFacade()::table('financialYear')->where('id', '!=',$id)->update(['isSetAsDefault' => false]);
            }

            return $this->success(200, Lang::get('financialYearMessages.basic.SUCC_UPDATE'), $financialYearData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function delete Financial year.
     * 
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "Financial year deleted successfully.",
     *      $data => null
     * 
     */
    public function deleteFinancialYear($id)
    {
        try {
        
            $dbWorkflowEmpGroup = $this->store->getById($this->financialYearModel, $id);
            if (is_null($dbWorkflowEmpGroup)) {
                return $this->error(404, Lang::get('workflowEmployeeGroupMessages.basic.ERR_NONEXISTENT_TERMINATION_REASON'), null);
            }

            $this->store->getFacade()::table('financialYear')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('financialYearMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_DELETE'), null);
        }
    }

   
}