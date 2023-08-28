<?php

namespace App\Services;

use App\Library\Interfaces\ModelReaderInterface;
use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use Illuminate\Support\Facades\Hash;
use App\Library\Store;
use App\Library\ModelValidator;
/**
 * Name: WorkflowStateTransitionService
 * Purpose: Performs tasks related to the WorkflowStateTransition model.
 * Description: WorkflowStateTransition Service class is called by the WorkflowStateTransitionController where the requests related
 * to WorkflowStateTransition Model (basic operations and others). Table that is being modified is workflowStateTransition.
 * Module Creator: Tharindu 
 */
class WorkflowStateTransitionService extends BaseService
{
    private $store;

    private $workflowStateTransitionModel;

    public function __construct(Store $store, ModelReaderInterface $workflowStateTransitionReader)
    {
        $this->store = $store;
        $this->workflowStateTransitionModel = $workflowStateTransitionReader->getModel('workflowStateTransition');
    }
    

    /**
     * Following function creates a WorkflowStateTransition.
     * 
     * @param $WorkflowStateTransition array containing the WorkflowStateTransition data
     * @return int | String | array
     * 
     * Usage:
     * $WorkflowStateTransition => ["actionName": "Relative"]
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "workflowStateTransition created Successuflly",
     * $data => {"actionName": "Relative"}//$data has a similar set of values as the input
     *  */

    public function createWorkflowStateTransition($workflowStateTransition)
    {
        try {

            $workflowStateTransition['permittedEmployees'] = (!empty($workflowStateTransition['permittedEmployees'])) ? json_encode($workflowStateTransition['permittedEmployees']) : null;
            $workflowStateTransition['permittedRoles'] = (!empty($workflowStateTransition['permittedRoles'])) ? json_encode($workflowStateTransition['permittedRoles']) : null;

            $validationResponse = ModelValidator::validate($this->workflowStateTransitionModel, $workflowStateTransition, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('workflowStateTransitionMessages.basic.ERR_CREATE'), $validationResponse);
            }
          
            $newWorkflowStateTransition = $this->store->insert($this->workflowStateTransitionModel, $workflowStateTransition, true);

            return $this->success(201, Lang::get('workflowStateTransitionMessages.basic.SUCC_CREATE'), $newWorkflowStateTransition);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowStateTransitionMessages.basic.ERR_CREATE'), null);
        }
    }


    /** 
     * Following function retrives all workflowStateTransition.
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "workflowStateTransition created Successuflly",
     *      $data => {{"id": 1, actionName": "Relative"}, {"id": 1, actionName": "Relative"}}
     * ] 
     */
    public function getAllWorkflowStateTransition($permittedFields, $options)
    {
        try {
            
            $permittedWorkFlowId = $options['filterBy'];
            $customWhereClauses = isset($options['filterBy']) ? [['isDelete','=',false],['workflowId', '=',  $permittedWorkFlowId]] : [['isDelete','=',false]];
            $filteredWorkflowStateTransition = $this->store->getAll(
                $this->workflowStateTransitionModel,
                $permittedFields,
                $options,
                [],
                $customWhereClauses);
            
            foreach ($filteredWorkflowStateTransition['data'] as $key => $value) {
                $value = (array) $value;
                
                $filteredWorkflowStateTransition['data'][$key]->permittedRoles = (!empty($value['permittedRoles'])) ? json_decode($value['permittedRoles']) : [];
                $filteredWorkflowStateTransition['data'][$key]->permittedEmployees = (!empty($value['permittedEmployees'])) ? json_decode($value['permittedEmployees']) : [];
                
            }


            return $this->success(200, Lang::get('workflowStateTransitionMessages.basic.SUCC_ALL_RETRIVE'), $filteredWorkflowStateTransition);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowStateTransitionMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /** 
     * Following function retrives a single WorkflowStateTransition for a provided id.
     * 
     * @param $id workflowStateTransition id
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
    public function getWorkflowStateTransition($id)
    {
        try {
            
           
            $workflowStateTransition = $this->store->getById($this->workflowStateTransitionModel, $id);
            if (empty($workflowStateTransition)) {
                return $this->error(404, Lang::get('workflowStateTransitionMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            return $this->success(200, Lang::get('workflowStateTransitionMessages.basic.SUCC_SINGLE_RETRIVE'), $workflowStateTransition);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowStateTransitionMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }

    /**
     * Following function updates a workflowStateTransition.
     * 
     * @param $id workflowStateTransition id
     * @param $WorkflowStateTransition array containing WorkflowStateTransition data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "workflowStateTransition updated successfully.",
     *      $data => {"id": 1, actionName": "Relative"} // has a similar set of data as entered to updating WorkflowStateTransition.
     * 
     */
    public function updateWorkflowStateTransition($id, $workflowStateTransition)
    {
        try {

            $workflowStateTransition['permittedEmployees'] = (!empty($workflowStateTransition['permittedEmployees'])) ? json_encode($workflowStateTransition['permittedEmployees']) : null;
            $workflowStateTransition['permittedRoles'] = (!empty($workflowStateTransition['permittedRoles'])) ? json_encode($workflowStateTransition['permittedRoles']) : null;

            $validationResponse = ModelValidator::validate($this->workflowStateTransitionModel, $workflowStateTransition, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('workflowStateTransitionMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbWorkflowStateTransition = $this->store->getById($this->workflowStateTransitionModel, $id);
            if (is_null($dbWorkflowStateTransition)) {
                return $this->error(404, Lang::get('workflowStateTransitionMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }
            $result = $this->store->updateById($this->workflowStateTransitionModel, $id, $workflowStateTransition);
            if (!$result) {
                return $this->error(502, Lang::get('workflowStateTransitionMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('workflowStateTransitionMessages.basic.SUCC_UPDATE'), $workflowStateTransition);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowStateTransitionMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     * 
     * @param $id workflowStateTransition id
     * @param $WorkflowStateTransition array containing WorkflowStateTransition data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "workflowStateTransition deleted successfully.",
     *      $data => null
     * 
     */
    public function softDeleteWorkflowStateTransition($id)
    {
        try {
            
            $dbWorkflowStateTransition = $this->store->getById($this->workflowStateTransitionModel, $id);
            if (is_null($dbWorkflowStateTransition)) {
                return $this->error(404, Lang::get('workflowStateTransitionMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }
            $this->store->getFacade()::table('workflowStateTransitions')->where('id', $id)->update(['isDelete' => true]);
            $actionModelName = $this->workflowStateTransitionModel->getName();
            $result = $this->store->getFacade()::table($actionModelName)
                ->where('id', $id)
                ->update(['isDelete' => true]);
            return $this->success(200, Lang::get('workflowStateTransitionMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowStateTransitionMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Get all records for workflow transitions
     *
     * @param  $options options as array
     * @return \Illuminate\Support\Collection | Exception
     *
     * Usage:
     *
     * $model => Model $userModel
     * $columns => ['id', 'email']
     * $options => ['order' => ['id', 'DESC'], 'offset' => 10, 'limit' => 10]
     * $with => ['employee']
     *
     * Sample output:
     * \Illuminate\Support\Collection
     *
     */
    public function getAllWorkflowTransitions( $options = [])
    {
        try {
                  $queryBuilder = DB::table('workflow_state_transitions')
                            ->select( 'workflow_state_transitions.id','workflow_state_transitions.workflowId','workflow_state_transitions.actionId','workflow_state_transitions.postState',
                            'workflow_state_transitions.priorState as priorState','workflow_actions.actionName',
                            'workflow_state.stateName as priorStateName','workflow_state_post.stateName as postStateName')
                            ->leftJoin('workflow_actions', 'workflow_state_transitions.actionId', '=', 'workflow_actions.id')
                            ->leftJoin('workflow_state','workflow_state_transitions.priorState','=', 'workflow_state.id')
                            ->leftJoin('workflow_state as workflow_state_post','workflow_state_transitions.postState','=', 'workflow_state_post.id')
                            ->where([['workflow_state_transitions.workflowId','=',21],['workflow_state_transitions.isDelete','=',false]]);
            $total = null;
            if (!empty($options["pageSize"]) && !is_null($options["current"])) {
                $page = (int) ($options["current"] > 0 ? (int) $options["current"] - 1: 0) * $options["pageSize"];
                $total = $queryBuilder->count();
                $queryBuilder = $queryBuilder->limit($options["pageSize"])->offset($page);
            }
            $results = $queryBuilder->get();

            if (!empty($total)) {
                return [
                    "current" => $options["current"],
                    "pageSize" => $options["pageSize"],
                    "total" => $total,
                    "data" => $results
                ];
            }
            return  $results ;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }

    }
   
}