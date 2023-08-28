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
/**
 * Name: WorkflowApproverPoolsService
 * Purpose: Performs tasks related to the WorkflowApproverPool model.
 * Description: WorkflowApproverPool Service class is called by the WorkflowApproverPoolController where the requests related
 * to WorkflowApproverPool Model (basic operations and others). Table that is being modified is WorkflowApproverPool.
 * Module Creator: Tharindu Darshana
 */
class WorkflowApproverPoolsService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $workflowApproverPoolModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->workflowApproverPoolModel = $this->getModel('workflowApproverPool', true);
    }
    

    /**
     * Following function creates a Workflow Approver Pool.
     * 
     * @param $workflowApproverPool array containing the Workflow Approver Pool data
     * @return int | String | array
     * 
     * Usage:
     * $workflowApproverPool => ["poolName": "Pool 1"]
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "workflow Employee Group created Successuflly",
     * $data => {"name": "Group 1"}//$data has a similar set of values as the input
     *  */

    public function createWorkflowApproverPool($workflowApproverPool)
    {
        try {
            $validationResponse = ModelValidator::validate($this->workflowApproverPoolModel, $workflowApproverPool, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('workflowApproverPoolMessages.basic.ERR_CREATE'), $validationResponse);
            }
          
            $newWorkflowApproverPool = $this->store->insert($this->workflowApproverPoolModel, $workflowApproverPool, true);

            return $this->success(201, Lang::get('workflowApproverPoolMessages.basic.SUCC_CREATE'), $newWorkflowApproverPool);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowApproverPoolMessages.basic.ERR_CREATE'), null);
        }
    }


    /** 
     * Following function retrives all  Workflow Approver Pools.
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Workflow Approver Pools retrived Successuflly",
     *      $data => {{"id": 1, poolName": "Pool 1"}, {"id": 1, poolName": "Pool 2"}}
     * ] 
     */
    public function getAllWorkflowApproverPools($permittedFields, $options)
    {
        try {
            $filteredWorkflowApproverPools = $this->store->getAll(
                $this->workflowApproverPoolModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]);

            if (!empty($filteredWorkflowApproverPools['data'])) {
                foreach ($filteredWorkflowApproverPools['data'] as $key => $value) {
                    $value = (array) $value;
                    $filteredWorkflowApproverPools['data'][$key]->poolPermittedEmployees = (!empty($value['poolPermittedEmployees'])) ? json_decode($value['poolPermittedEmployees']) : [];
                }
            }

            return $this->success(200, Lang::get('workflowApproverPoolMessages.basic.SUCC_ALL_RETRIVE'), $filteredWorkflowApproverPools);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowApproverPoolMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    

    /**
     * Following function updates a Workflow Approver Pool.
     * 
     * @param $id Workflow Approver Pool id
     * @param $workflowApproverPool array containing Workflow Approver Pools data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "Workflow Approver Pools updated successfully.",
     *      $data => {"id": 1, actionName": "Relative"} // has a similar set of data as entered to updating Workflow Approver Pools.
     * 
     */
    public function updateWorkflowApproverPool($id, $workflowApproverPool)
    {
        try {
               
            $validationResponse = ModelValidator::validate($this->workflowApproverPoolModel, $workflowApproverPool, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('workflowApproverPoolMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbWorkflowApproverPool = $this->store->getById($this->workflowApproverPoolModel, $id);
            if (is_null($dbWorkflowApproverPool)) {
                return $this->error(404, Lang::get('workflowApproverPoolMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            //check whether has pool related pending workflow instance
            $approverLevelData= DB::table('workflowApprovalLevel')
            ->where('workflowApprovalLevel.approverPoolId', '=', $id)
            ->where('workflowApprovalLevel.isDelete', '=', false)
            ->groupBy('workflowApprovalLevel.workflowId')
            ->pluck('workflowApprovalLevel.workflowId')->toArray();

            $relatedWorkflowInstanceCount= DB::table('workflowInstance')
            ->whereIn('workflowInstance.workflowId', $approverLevelData)
            ->where('workflowInstance.isDelete', '=', false)
            ->where('workflowInstance.currentStateId', '=', 1)->count();

            if ($relatedWorkflowInstanceCount > 0) {
                return $this->error(502, Lang::get('workflowApproverPoolMessages.basic.ERR_NOTALLOWED_UPDATE'), $id);
            }
            
            $result = $this->store->updateById($this->workflowApproverPoolModel, $id, $workflowApproverPool);

            if (!$result) {
                return $this->error(502, Lang::get('workflowApproverPoolMessages.basic.ERR_UPDATE'), $id);
            }


            return $this->success(200, Lang::get('workflowApproverPoolMessages.basic.SUCC_UPDATE'), $workflowApproverPool);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowApproverPoolMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function delete Workflow Approver Pool.
     * 
     * @param $id Workflow Approver Pool id
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "Workflow Approver Pool deleted successfully.",
     *      $data => null
     * 
     */
    public function deleteWorkflowApproverPool($id)
    {
        try {
        
            $dbWorkflowEmpGroup = $this->store->getById($this->workflowApproverPoolModel, $id);
            if (is_null($dbWorkflowEmpGroup)) {
                return $this->error(404, Lang::get('workflowEmployeeGroupMessages.basic.ERR_NONEXISTENT_TERMINATION_REASON'), null);
            }

            //check whether has pool related pending workflow instance
            $approverLevelData= DB::table('workflowApprovalLevel')
            ->where('workflowApprovalLevel.approverPoolId', '=', $id)
            ->where('workflowApprovalLevel.isDelete', '=', false)
            ->groupBy('workflowApprovalLevel.workflowId')
            ->pluck('workflowApprovalLevel.workflowId')->toArray();

            $relatedWorkflowInstanceCount= DB::table('workflowInstance')
            ->whereIn('workflowInstance.workflowId', $approverLevelData)
            ->where('workflowInstance.isDelete', '=', false)
            ->where('workflowInstance.currentStateId', '=', 1)->count();

            if ($relatedWorkflowInstanceCount > 0) {
                return $this->error(502, Lang::get('workflowApproverPoolMessages.basic.ERR_NOTALLOWED_DELETE'), $id);
            }

            $this->store->getFacade()::table('workflowApproverPool')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('workflowApproverPoolMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowApproverPoolMessages.basic.ERR_DELETE'), null);
        }
    }

   
}