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
 * Name: ClaimCategoryService
 * Purpose: Performs tasks related to the ClaimCategory model.
 * Description: WorkflowApproverPool Service class is called by the ClaimCategoryController where the requests related
 * to ClaimCategory Model (basic operations and others). Table that is being modified is ClaimCategory.
 * Module Creator: Tharindu Darshana
 */
class ClaimCategoryService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $claimCategoryModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->claimCategoryModel = $this->getModel('claimCategory', true);
    }
    

    /**
     * Following function creates claim actegory
     * 
     * @param $claimCategoryData array containing the Calim Category data
     * @return int | String | array
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "Claim category created Successuflly",
     * $data => {"name": "Group 1"}//$data has a similar set of values as the input
     *  */

    public function createClaimCategory($claimCategoryData)
    {
        try {
            $validationResponse = ModelValidator::validate($this->claimCategoryModel, $claimCategoryData, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('financialYearMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $newfinancialYear = $this->store->insert($this->claimCategoryModel, $claimCategoryData, true);

            return $this->success(201, Lang::get('financialYearMessages.basic.SUCC_CREATE'), $newfinancialYear);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_CREATE'), null);
        }
    }


    /** 
     * Following function retrives all Claim Categories.
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Claim categories retrived Successuflly",
     *      $data => {{"id": 1, poolName": "Pool 1"}, {"id": 1, poolName": "Pool 2"}}
     * ] 
     */
    public function getAllClaimCategories($permittedFields, $options)
    {
        try {
            $filteredWorkflowApproverPools = $this->store->getAll(
                $this->claimCategoryModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]);


            return $this->success(200, Lang::get('financialYearMessages.basic.SUCC_ALL_RETRIVE'), $filteredWorkflowApproverPools);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    

    /**
     * Following function updates a claim category
     * 
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "Claim Category updated successfully.",
     *      $data => {"id": 1, actionName": "Relative"} // has a similar set of data as entered to updating Workflow Approver Pools.
     * 
     */
    public function updateClaimCategory($id, $claimCategoryData)
    {
        try {
               
            $validationResponse = ModelValidator::validate($this->claimCategoryModel, $claimCategoryData, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('financialYearMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbClaimCategory = $this->store->getById($this->claimCategoryModel, $id);
            if (is_null($dbClaimCategory)) {
                return $this->error(404, Lang::get('financialYearMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            $result = $this->store->updateById($this->claimCategoryModel, $id, $claimCategoryData);

            if (!$result) {
                return $this->error(502, Lang::get('financialYearMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('financialYearMessages.basic.SUCC_UPDATE'), $claimCategoryData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function delete Claim Category
     *  
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "Claim category deleted successfully.",
     *      $data => null
     * 
     */
    public function deleteClaimCategory($id)
    {
        try {
        
            $dbClaimCategory = $this->store->getById($this->claimCategoryModel, $id);
            if (is_null($dbClaimCategory)) {
                return $this->error(404, Lang::get('workflowEmployeeGroupMessages.basic.ERR_NONEXISTENT_TERMINATION_REASON'), null);
            }

            //check whether has any claim category link with calaim type
            $relatedClaimTypeRecords = $this->store->getFacade()::table('claimType')
                ->where('claimCategoryId', $id)
                ->first();

            if (!empty($relatedClaimTypeRecords)) {
                return $this->error(502, Lang::get('financialYearMessages.basic.CLAIM_CAT_DELETE_ERR_NOTALLOWED'), null);
            }

            $this->store->getFacade()::table('claimCategory')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('financialYearMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_DELETE'), null);
        }
    }

   
}