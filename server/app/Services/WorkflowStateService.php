<?php

namespace App\Services;

use App\Library\Interfaces\ModelReaderInterface;
use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use Illuminate\Support\Facades\Hash;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Traits\JsonModelReader;
/**
 * Name: WorkflowStateService
 * Purpose: Performs tasks related to the WorkflowState model.
 * Description: WorkflowState Service class is called by the WorkflowStateController where the requests related
 * to WorkflowState Model (basic operations and others). Table that is being modified is workflowState.
 * Module Creator: Tharindu 
 */
class WorkflowStateService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $workflowStateModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->workflowStateModel = $this->getModel('workflowState', true);
    }
    

    /**
     * Following function creates a WorkflowState.
     * 
     * @param $WorkflowState array containing the WorkflowState data
     * @return int | String | array
     * 
     * Usage:
     * $WorkflowState => ["actionName": "Relative"]
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "workflowState created Successuflly",
     * $data => {"actionName": "Relative"}//$data has a similar set of values as the input
     *  */

    public function createWorkflowState($workflowState)
    {
        try {
             
            $validationResponse = ModelValidator::validate($this->workflowStateModel, $workflowState, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('workflowStateMessages.basic.ERR_CREATE'), $validationResponse);
            }
          
            $newWorkflowState = $this->store->insert($this->workflowStateModel, $workflowState, true);

            return $this->success(201, Lang::get('workflowStateMessages.basic.SUCC_CREATE'), $newWorkflowState);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowStateMessages.basic.ERR_CREATE'), null);
        }
    }


    /** 
     * Following function retrives all workflowState.
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "workflowState created Successuflly",
     *      $data => {{"id": 1, actionName": "Relative"}, {"id": 1, actionName": "Relative"}}
     * ] 
     */
    public function getAllWorkflowState($permittedFields, $options)
    {
        try {
            $filteredWorkflowState = $this->store->getAll(
                $this->workflowStateModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]);
            return $this->success(200, Lang::get('workflowStateMessages.basic.SUCC_ALL_RETRIVE'), $filteredWorkflowState);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowStateMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /** 
     * Following function retrives a single WorkflowState for a provided id.
     * 
     * @param $id workflowState id
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
    public function getWorkflowState($id)
    {
        try {
            
           
            $workflowState = $this->store->getById($this->workflowStateModel, $id);
            if (empty($workflowState)) {
                return $this->error(404, Lang::get('workflowStateMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            return $this->success(200, Lang::get('workflowStateMessages.basic.SUCC_SINGLE_RETRIVE'), $workflowState);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowStateMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }





    /**
     * Following function updates a workflowState.
     * 
     * @param $id workflowState id
     * @param $WorkflowState array containing WorkflowState data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "workflowState updated successfully.",
     *      $data => {"id": 1, actionName": "Relative"} // has a similar set of data as entered to updating WorkflowState.
     * 
     */
    public function updateWorkflowState($id, $workflowState)
    {
        try {
               
           

            $validationResponse = ModelValidator::validate($this->workflowStateModel, $workflowState, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('workflowStateMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbWorkflowState = $this->store->getById($this->workflowStateModel, $id);
            if (is_null($dbWorkflowState)) {
                return $this->error(404, Lang::get('workflowStateMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            $result = $this->store->updateById($this->workflowStateModel, $id, $workflowState);

            if (!$result) {
                return $this->error(502, Lang::get('workflowStateMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('workflowStateMessages.basic.SUCC_UPDATE'), $workflowState);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowStateMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     * 
     * @param $id workflowState id
     * @param $WorkflowState array containing WorkflowState data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "workflowState deleted successfully.",
     *      $data => null
     * 
     */
    public function softDeleteWorkflowState($id)
    {
        try {
            
            $dbWorkflowState = $this->store->getById($this->workflowStateModel, $id);
            if (is_null($dbWorkflowState)) {
                return $this->error(404, Lang::get('workflowStateMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            $defaultStateIds = [1, 2, 3, 4];
            
            // check whether this is default state 
            if (in_array($id, $defaultStateIds)) {
                return $this->error(404, Lang::get('workflowStateMessages.basic.ERR_IS_DEFAULT_STATE'), null);
            }
            
            //check whether state is link with workflow
            $linkedWorkflowsCount = $this->store->getFacade()::table('workflowDefine')
                            ->whereJsonContains('sucessStates', (int)$id)
                            ->orwhereJsonContains('failureStates', (int)$id)
                            ->where('isDelete', false)
                            ->count();                        
            if ($linkedWorkflowsCount > 0) {
                return $this->error(404, Lang::get('workflowStateMessages.basic.ERR_HAS_LINKED_WORKFLOWS'), null);
            }

            //check whether state linked with state transition
            $linkedStateTransitions = $this->store->getFacade()::table('workflowStateTransitions')
                            ->where('priorStateId', (int)$id)
                            ->orWhere('postStateId', (int)$id)
                            ->where('isDelete', false)->count();

            if ($linkedStateTransitions > 0) {
                return $this->error(404, Lang::get('workflowStateMessages.basic.ERR_HAS_LINKED_STATE_TRANSITION'), null);
            }


            $this->store->getFacade()::table('workflowState')->where('id', $id)->update(['isDelete' => true]);


            $actionModelName = $this->workflowStateModel->getName();
            $result = $this->store->getFacade()::table($actionModelName)
                ->where('id', $id)
                ->update(['isDelete' => true]);

            return $this->success(200, Lang::get('workflowStateMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowStateMessages.basic.ERR_DELETE'), null);
        }
    }

   
}