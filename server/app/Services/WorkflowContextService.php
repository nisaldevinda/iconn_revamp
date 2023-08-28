<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Traits\JsonModelReader;
/**
 * Name: WorkflowContextService
 * Purpose: Performs tasks related to the WorkflowContext model.
 * Description: WorkflowContext Service class is called by the WorkflowContextController where the requests related
 * to WorkflowContext Model (basic operations and others). Table that is being modified is workflowContext.
 * Module Creator: Tharindu 
 */
class WorkflowContextService extends BaseService
{
    use  JsonModelReader;

    private $store;

    private $workflowContextModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->workflowContextModel = $this->getModel('workflowContext', true);
    }
    

    /**
     * Following function creates a WorkflowContext.
     * 
     * @param $WorkflowContext array containing the WorkflowContext data
     * @return int | String | array
     * 
     * Usage:
     * $WorkflowContext => ["actionName": "Relative"]
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "workflowContext created Successuflly",
     * $data => {"actionName": "Relative"}//$data has a similar set of values as the input
     *  */

    public function createWorkflowContext($workflowContext)
    {
        try {
             
            $validationResponse = ModelValidator::validate($this->workflowContextModel, $workflowContext, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('workflowContextMessages.basic.ERR_CREATE'), $validationResponse);
            }
          
            $newWorkflowContext = $this->store->insert($this->workflowContextModel, $workflowContext, true);

            return $this->success(201, Lang::get('workflowContextMessages.basic.SUCC_CREATE'), $newWorkflowContext);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowContextMessages.basic.ERR_CREATE'), null);
        }
    }


    /** 
     * Following function retrives all workflowContext.
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "workflowContext created Successuflly",
     *      $data => {{"id": 1, actionName": "Relative"}, {"id": 1, actionName": "Relative"}}
     * ] 
     */
    public function getAllWorkflowContext($permittedFields, $options)
    {
        try {
            $filteredWorkflowContext = $this->store->getAll(
                $this->workflowContextModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]);
            return $this->success(200, Lang::get('workflowContextMessages.basic.SUCC_ALL_RETRIVE'), $filteredWorkflowContext);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowContextMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /** 
     * Following function retrives a single WorkflowContext for a provided id.
     * 
     * @param $id workflowContext id
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
    public function getWorkflowContext($id)
    {
        try {
            
           
            $workflowContext = $this->store->getById($this->workflowContextModel, $id);
            if (empty($workflowContext)) {
                return $this->error(404, Lang::get('workflowContextMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            return $this->success(200, Lang::get('workflowContextMessages.basic.SUCC_SINGLE_RETRIVE'), $workflowContext);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowContextMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }





    /**
     * Following function updates a workflowContext.
     * 
     * @param $id workflowContext id
     * @param $WorkflowContext array containing WorkflowContext data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "workflowContext updated successfully.",
     *      $data => {"id": 1, actionName": "Relative"} // has a similar set of data as entered to updating WorkflowContext.
     * 
     */
    public function updateWorkflowContext($id, $workflowContext)
    {
        try {
               
            $validationResponse = ModelValidator::validate($this->workflowContextModel, $workflowContext, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('workflowContextMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbWorkflowContext = $this->store->getById($this->workflowContextModel, $id);
            if (is_null($dbWorkflowContext)) {
                return $this->error(404, Lang::get('workflowContextMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            $result = $this->store->updateById($this->workflowContextModel, $id, $workflowContext);

            if (!$result) {
                return $this->error(502, Lang::get('workflowContextMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('workflowContextMessages.basic.SUCC_UPDATE'), $workflowContext);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowContextMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     * 
     * @param $id workflowContext id
     * @param $WorkflowContext array containing WorkflowContext data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "workflowContext deleted successfully.",
     *      $data => null
     * 
     */
    public function softDeleteWorkflowContext($id)
    {
        try {
            
            $dbWorkflowContext = $this->store->getById($this->workflowContextModel, $id);
            if (is_null($dbWorkflowContext)) {
                return $this->error(404, Lang::get('workflowContextMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }
            //check record exist in workflowDefine
            $workFlowDefine = $this->store->getFacade()::table('workflowDefine')
                            ->where('contextId', $id)
                            ->where('isDelete', false)->count();
            if ($workFlowDefine > 0) {
                return $this->error(400, Lang::get('workflowContextMessages.basic.ERR_NOTALLOWED'), null);
            }
            //check record exist in workflowEmployeeGroup
            $workflowEmployeeGroup = $this->store->getFacade()::table('workflowEmployeeGroup')
                            ->where('contextId', $id)
                            ->where('isDelete', false)->count();
            if ($workflowEmployeeGroup > 0) {
                return $this->error(400, Lang::get('workflowContextMessages.basic.ERR_NOTALLOWED'), null);
            }
            $this->store->getFacade()::table('workflowContext')->where('id', $id)->update(['isDelete' => true]);


            $actionModelName = $this->workflowContextModel->getName();
            $result = $this->store->getFacade()::table($actionModelName)
                ->where('id', $id)
                ->update(['isDelete' => true]);

            return $this->success(200, Lang::get('workflowContextMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowContextMessages.basic.ERR_DELETE'), null);
        }
    }

   
}