<?php

namespace App\Services;

use App\Library\Interfaces\ModelReaderInterface;
use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Traits\JsonModelReader;
/**
 * Name: WorkflowPermissionService
 * Purpose: Performs tasks related to the WorkflowPermission model.
 * Description: WorkflowPermission Service class is called by the WorkflowPermissionController where the requests related
 * to WorkflowPermission Model (basic operations and others). Table that is being modified is workflowPermission.
 * Module Creator: Tharindu 
 */
class WorkflowPermissionService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $workflowPermissionModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->workflowPermissionModel = $this->getModel('workflowPermission', true);
    }
    

    /**
     * Following function creates a WorkflowPermission.
     * 
     * @param $WorkflowPermission array containing the WorkflowPermission data
     * @return int | String | array
     * 
     * Usage:
     * $WorkflowPermission => ["actionName": "Relative"]
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "workflowPermission created Successuflly",
     * $data => {"actionName": "Relative"}//$data has a similar set of values as the input
     *  */

    public function createWorkflowPermission($workflowPermission)
    {
        try {
             
            $validationResponse = ModelValidator::validate($this->workflowPermissionModel, $workflowPermission, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('workflowPermissionMessages.basic.ERR_CREATE'), $validationResponse);
            }
          
            $newWorkflowPermission = $this->store->insert($this->workflowPermissionModel, $workflowPermission, true);

            return $this->success(201, Lang::get('workflowPermissionMessages.basic.SUCC_CREATE'), $newWorkflowPermission);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowPermissionMessages.basic.ERR_CREATE'), null);
        }
    }


    /** 
     * Following function retrives all workflowPermission.
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "workflowPermission created Successuflly",
     *      $data => {{"id": 1, actionName": "Relative"}, {"id": 1, actionName": "Relative"}}
     * ] 
     */
    public function getAllWorkflowPermission($permittedFields, $options)
    {
        try {
            $filteredWorkflowPermission = $this->store->getAll(
                $this->workflowPermissionModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]);
            return $this->success(200, Lang::get('workflowPermissionMessages.basic.SUCC_ALL_RETRIVE'), $filteredWorkflowPermission);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowPermissionMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /** 
     * Following function retrives a single WorkflowPermission for a provided id.
     * 
     * @param $id workflowPermission id
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
    public function getWorkflowPermission($id)
    {
        try {
            
           
            $workflowPermission = $this->store->getById($this->workflowPermissionModel, $id);
            if (empty($workflowPermission)) {
                return $this->error(404, Lang::get('workflowPermissionMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            return $this->success(200, Lang::get('workflowPermissionMessages.basic.SUCC_SINGLE_RETRIVE'), $workflowPermission);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowPermissionMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }





    /**
     * Following function updates a workflowPermission.
     * 
     * @param $id workflowPermission id
     * @param $WorkflowPermission array containing WorkflowPermission data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "workflowPermission updated successfully.",
     *      $data => {"id": 1, actionName": "Relative"} // has a similar set of data as entered to updating WorkflowPermission.
     * 
     */
    public function updateWorkflowPermission($id, $workflowPermission)
    {
        try {
               
           

            $validationResponse = ModelValidator::validate($this->workflowPermissionModel, $workflowPermission, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('workflowPermissionMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbWorkflowPermission = $this->store->getById($this->workflowPermissionModel, $id);
            if (is_null($dbWorkflowPermission)) {
                return $this->error(404, Lang::get('workflowPermissionMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            $result = $this->store->updateById($this->workflowPermissionModel, $id, $workflowPermission);

            if (!$result) {
                return $this->error(502, Lang::get('workflowPermissionMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('workflowPermissionMessages.basic.SUCC_UPDATE'), $workflowPermission);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowPermissionMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     * 
     * @param $id workflowPermission id
     * @param $WorkflowPermission array containing WorkflowPermission data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "workflowPermission deleted successfully.",
     *      $data => null
     * 
     */
    public function softDeleteWorkflowPermission($id)
    {
        try {
            
            $dbWorkflowPermission = $this->store->getById($this->workflowPermissionModel, $id);
            if (is_null($dbWorkflowPermission)) {
                return $this->error(404, Lang::get('workflowPermissionMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }
            
            $this->store->getFacade()::table('workflowPermission')->where('id', $id)->update(['isDelete' => true]);


            $actionModelName = $this->workflowPermissionModel->getName();
            $result = $this->store->getFacade()::table($actionModelName)
                ->where('id', $id)
                ->update(['isDelete' => true]);

            return $this->success(200, Lang::get('workflowPermissionMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowPermissionMessages.basic.ERR_DELETE'), null);
        }
    }

   
}