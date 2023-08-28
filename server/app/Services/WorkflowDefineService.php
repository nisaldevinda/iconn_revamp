<?php

namespace App\Services;

use App\Library\Interfaces\ModelReaderInterface;
use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use Illuminate\Support\Facades\DB;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Traits\JsonModelReader;
/**
 * Name: WorkflowDefineService
 * Purpose: Performs tasks related to the WorkflowDefine model.
 * Description: WorkflowDefine Service class is called by the WorkflowDefineController where the requests related
 * to WorkflowDefine Model (basic operations and others). Table that is being modified is workflowDefine.
 * Module Creator: Tharindu 
 */
class WorkflowDefineService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $workflowDefineModel;
    private $workflowApprovalLevelModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->workflowDefineModel = $this->getModel('workflowDefine', true);
        $this->workflowApprovalLevelModel = $this->getModel('workflowApprovalLevel', true);
    }
    

    /**
     * Following function creates a WorkflowDefine.
     * 
     * @param $WorkflowDefine array containing the WorkflowDefine data
     * @return int | String | array
     * 
     * Usage:
     * $WorkflowDefine => ["actionName": "Relative"]
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "workflowDefine created Successuflly",
     * $data => {"actionName": "Relative"}//$data has a similar set of values as the input
     *  */

    public function createWorkflowDefine($workflowDefine)
    {
        try {
             
            $validationResponse = ModelValidator::validate($this->workflowDefineModel, $workflowDefine, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('workflowDefineMessages.basic.ERR_CREATE'), $validationResponse);
            }
          
            $newWorkflowDefine = $this->store->insert($this->workflowDefineModel, $workflowDefine, true);

            return $this->success(201, Lang::get('workflowDefineMessages.basic.SUCC_CREATE'), $newWorkflowDefine);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowDefineMessages.basic.ERR_CREATE'), null);
        }
    }


    /** 
     * Following function retrives all workflowDefine.
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "workflowDefine created Successuflly",
     *      $data => {{"id": 1, actionName": "Relative"}, {"id": 1, actionName": "Relative"}}
     * ] 
     */
    public function getAllWorkflowDefine($permittedFields, $options)
    {
        try {
            $filteredWorkflowDefine = $this->store->getAll(
                $this->workflowDefineModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]);
            return $this->success(200, Lang::get('workflowDefineMessages.basic.SUCC_ALL_RETRIVE'), $filteredWorkflowDefine);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowDefineMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /** 
     * Following function retrives a single WorkflowDefine for a provided id.
     * 
     * @param $id workflowDefine id
     * @return int | String | array
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Marital Status created Successuflly",
     *      $data => {"id": 1, actionName": "Relative"}
     * ]
     */
    public function getWorkflowDefine($id)
    {
        try {
            
           
            $workflowDefine = $this->store->getById($this->workflowDefineModel, $id);
            if (empty($workflowDefine)) {
                return $this->error(404, Lang::get('workflowDefineMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            return $this->success(200, Lang::get('workflowDefineMessages.basic.SUCC_SINGLE_RETRIVE'), $workflowDefine);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowDefineMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }





    /**
     * Following function updates a workflowDefine.
     * 
     * @param $id workflowDefine id
     * @param $WorkflowDefine array containing WorkflowDefine data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "workflowDefine updated successfully.",
     *      $data => {"id": 1, actionName": "Relative"} // has a similar set of data as entered to updating WorkflowDefine.
     * 
     */
    public function updateWorkflowDefine($id, $workflowDefine)
    {
        try {
               
            $validationResponse = ModelValidator::validate($this->workflowDefineModel, $workflowDefine, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('workflowDefineMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $workflowDefine['employeeGroupId'] = !empty ($workflowDefine['employeeGroupId']) ? $workflowDefine['employeeGroupId'] : null;

            $dbWorkflowDefine = $this->store->getById($this->workflowDefineModel, $id);
            if (is_null($dbWorkflowDefine)) {
                return $this->error(404, Lang::get('workflowDefineMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            $result = $this->store->updateById($this->workflowDefineModel, $id, $workflowDefine);

            if (!$result) {
                return $this->error(502, Lang::get('workflowDefineMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('workflowDefineMessages.basic.SUCC_UPDATE'), $workflowDefine);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowDefineMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     * 
     * @param $id workflowDefine id
     * @param $WorkflowDefine array containing WorkflowDefine data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "workflowDefine deleted successfully.",
     *      $data => null
     * 
     */
    public function softDeleteWorkflowDefine($id)
    {
        try {
            
            $dbWorkflowDefine = $this->store->getById($this->workflowDefineModel, $id);
            if (is_null($dbWorkflowDefine)) {
                return $this->error(404, Lang::get('workflowDefineMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            //check whether has any workflow instances
            $linkedWorkflowInstances = $this->store->getFacade()::table('workflowInstance')
                            ->where('workflowId', (int)$id)
                            ->where('isDelete', false)->count();
            if ($linkedWorkflowInstances > 0) {
                return $this->error(404, Lang::get('workflowDefineMessages.basic.ERR_HAS_LINKED_WORKFLOW_INSTANCES'), null);
            }

            $this->store->getFacade()::table('workflowDefine')->where('id', $id)->update(['isDelete' => true]);

            $actionModelName = $this->workflowDefineModel->getName();
            $result = $this->store->getFacade()::table($actionModelName)
                ->where('id', $id)
                ->update(['isDelete' => true]);

            return $this->success(200, Lang::get('workflowDefineMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowDefineMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function updates a workflowDefine.
     * 
     * @param $id workflowDefine id
     * @param $WorkflowDefine array containing WorkflowDefine data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "workflowDefine updated successfully.",
     *      $data => {"id": 1, actionName": "Relative"} // has a similar set of data as entered to updating WorkflowDefine.
     * 
     */
    public function updateWorkflowProcedureType($id, $workflowDefine)
    {
        try {
                          
            $dbWorkflowDefine = $this->store->getById($this->workflowDefineModel, $id);
            if (is_null($dbWorkflowDefine)) {
                return $this->error(404, Lang::get('workflowDefineMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }
            $dbWorkflowDefine =  (array) $dbWorkflowDefine;
            $dbWorkflowDefine['isProcedureDefined'] = $workflowDefine['isProcedureDefined'];

            //check whether any pending workflow instance exsist
            $instanceCount = DB::table('workflowInstance')
                ->where('workflowInstance.workflowId', '=', $id)
                ->where('workflowInstance.currentStateId', '=', 1)
                ->where('workflowInstance.isDelete', '=', false)
                ->count();
            
            if ($instanceCount > 0) {
                return $this->error(500, Lang::get('workflowDefineMessages.basic.ERR_CANNOT_DELETE_WF_NODE_HAS_LINKED_WORKFLOW_INSTANCES'), null);
            }

            if (!$workflowDefine['isProcedureDefined']) {
                //get related approval levels
                $workFlowLevels = DB::table('workflowApprovalLevel')
                ->where('workflowId', '=', $id)
                ->where('isDelete', '=', false)
                ->get();

                if (!is_null($workFlowLevels) && sizeof($workFlowLevels) > 0) {
                    $workFlowLevels = DB::table('workflowApprovalLevel')
                    ->where('workflowId', '=', $id)
                    ->delete();
                }
            }

            $result = $this->store->updateById($this->workflowDefineModel, $id, $workflowDefine);

            if (!$result) {
                return $this->error(502, Lang::get('workflowDefineMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('workflowDefineMessages.basic.SUCC_UPDATE'), $workflowDefine);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowDefineMessages.basic.ERR_UPDATE'), null);
        }
    }


    /**
     * Following function updates a workflowDefine.
     * 
     * @param $id workflowDefine id
     * @param $WorkflowDefine array containing WorkflowDefine data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "workflowDefine updated successfully.",
     *      $data => {"id": 1, actionName": "Relative"} // has a similar set of data as entered to updating WorkflowDefine.
     * 
     */
    public function updateWorkflowLevelConfigurations($id, $workflowApprovalLevelConfig)
    {
        try {
                          
            $validationResponse = ModelValidator::validate($this->workflowApprovalLevelModel, $workflowApprovalLevelConfig, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('workflowDefineMessages.basic.ERR_UPDATE'), $validationResponse);
            }

        
            $workflowLevel = $this->store->getById($this->workflowApprovalLevelModel, $id);
            if (is_null($workflowLevel)) {
                return $this->error(404, Lang::get('workflowDefineMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            //check whether any pending workflow instance exsist
            $instanceCount = DB::table('workflowInstance')
                ->where('workflowInstance.workflowId', '=', $workflowLevel->workflowId)
                ->where('workflowInstance.currentStateId', '=', 1)
                ->where('workflowInstance.isDelete', '=', false)
                ->count();
            
            if ($instanceCount > 0) {
                return $this->error(500, Lang::get('workflowDefineMessages.basic.ERR_CANNOT_CHANGE_HAS_LINKED_WORKFLOW_INSTANCES'), null);
            }

            $result = $this->store->updateById($this->workflowApprovalLevelModel, $id, $workflowApprovalLevelConfig);

            if (!$result) {
                return $this->error(502, Lang::get('workflowDefineMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('workflowDefineMessages.basic.SUCC_UPDATE'), []);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowDefineMessages.basic.ERR_UPDATE'), null);
        }
    }


    /**
     * Following function updates a workflowDefine.
     * 
     * @param $id workflowDefine id
     * @param $WorkflowDefine array containing WorkflowDefine data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "workflowDefine updated successfully.",
     *      $data => {"id": 1, actionName": "Relative"} // has a similar set of data as entered to updating WorkflowDefine.
     * 
     */
    public function deleteWorkflowLevelConfigurations($id)
    {
        try {
            DB::beginTransaction();  
            $workflowLevel = $this->store->getById($this->workflowApprovalLevelModel, $id);
            if (is_null($workflowLevel)) {
                DB::rollback();
                return $this->error(404, Lang::get('workflowDefineMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            //check whether any pending workflow instance exsist
            $instanceCount = DB::table('workflowInstance')
                ->where('workflowInstance.workflowId', '=', $workflowLevel->workflowId)
                ->where('workflowInstance.currentStateId', '=', 1)
                ->where('workflowInstance.isDelete', '=', false)
                ->count();
            
            if ($instanceCount > 0) {
                return $this->error(500, Lang::get('workflowDefineMessages.basic.ERR_CANNOT_DELETE_HAS_LINKED_WORKFLOW_INSTANCES'), null);
            }

            $workflowId = $workflowLevel->workflowId;
            $levelSequnce = $workflowLevel->levelSequence;

            //delete particular level
            $deleteLevel = DB::table('workflowApprovalLevel')
                ->where('id', '=', $id)
                ->delete();
            
            if (!$deleteLevel) {
                DB::rollback();
                return $this->error($e->getCode(), Lang::get('workflowDefineMessages.basic.ERR_DELETE'), null);
            }

            //delete all the levels after current level sequence
            $deleteLevel = DB::table('workflowApprovalLevel')
                ->where('workflowId', '=', $workflowId)
                ->where('levelSequence', '>', $levelSequnce)
                ->delete();

            //get the num of level count
            $levelCount = DB::table('workflowApprovalLevel')
                ->where('workflowApprovalLevel.workflowId', '=', $workflowId)
                ->where('workflowApprovalLevel.isDelete', '=', false)
                ->count();

            $workflowDefineData = [
                'numOfApprovalLevels' => $levelCount
            ];
            
            $updateLevelCount = $this->store->updateById($this->workflowDefineModel, $workflowId, $workflowDefineData);

            DB::commit();
            return $this->success(200, Lang::get('workflowDefineMessages.basic.SUCC_DELETE'), []);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowDefineMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function creates a WorkflowDefine.
     * 
     * @param $WorkflowDefine array containing the WorkflowDefine data
     * @return int | String | array
     * 
     * Usage:
     * $WorkflowDefine => ["actionName": "Relative"]
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "workflowDefine created Successuflly",
     * $data => {"actionName": "Relative"}//$data has a similar set of values as the input
     *  */

    public function addWorkflowApproverLevel($workflowApprovalLevelData)
    {
        try {
            DB::beginTransaction();

            $validationResponse = ModelValidator::validate($this->workflowApprovalLevelModel, $workflowApprovalLevelData, false);
            if (!empty($validationResponse)) {
                DB::rollback();
                return $this->error(400, Lang::get('workflowDefineMessages.basic.ERR_CREATE'), $validationResponse);
            }

            //check whether any pending workflow instance exsist
            $instanceCount = DB::table('workflowInstance')
                ->where('workflowInstance.workflowId', '=', $workflowApprovalLevelData['workflowId'])
                ->where('workflowInstance.currentStateId', '=', 1)
                ->where('workflowInstance.isDelete', '=', false)
                ->count();
            
            if ($instanceCount > 0) {
                DB::rollback();
                return $this->error(500, Lang::get('workflowDefineMessages.basic.ERR_CANNOT_ADD_HAS_LINKED_WORKFLOW_INSTANCES'), null);
            }

          
            $newWorkflowDefine = $this->store->insert($this->workflowApprovalLevelModel, $workflowApprovalLevelData, true);

            //get the num of level count
            $levelCount = DB::table('workflowApprovalLevel')
                ->where('workflowApprovalLevel.workflowId', '=', $workflowApprovalLevelData['workflowId'])
                ->where('workflowApprovalLevel.isDelete', '=', false)
                ->count();

            $workflowDefineData = [
                'numOfApprovalLevels' => $levelCount
            ];
            
            $updateLevelCount = $this->store->updateById($this->workflowDefineModel, $workflowApprovalLevelData['workflowId'], $workflowDefineData);

            DB::commit();
            return $this->success(201, Lang::get('workflowDefineMessages.basic.SUCC_CREATE'), $newWorkflowDefine);

        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowDefineMessages.basic.ERR_CREATE'), null);
        }
    }

   
}