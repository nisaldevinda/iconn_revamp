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
 * Name: WorkflowEmployeeGroupService
 * Purpose: Performs tasks related to the WorkflowEmployeeGroup model.
 * Description: WorkflowEmployeeGroup Service class is called by the WorkflowEmployeeGroupController where the requests related
 * to WorkflowEmployeeGroup Model (basic operations and others). Table that is being modified is workflowEmployeeGroup.
 * Module Creator: Tharindu Darshana
 */
class WorkflowEmployeeGroupService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $workflowEmployeeGroupModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->workflowEmployeeGroupModel = $this->getModel('workflowEmployeeGroup', true);
    }
    

    /**
     * Following function creates a WorkflowEmployeeGroup.
     * 
     * @param $WorkflowEmployeeGroup array containing the WorkflowEmployeeGroup data
     * @return int | String | array
     * 
     * Usage:
     * $WorkflowEmployeeGroup => ["name": "Group 1"]
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "workflow Employee Group created Successuflly",
     * $data => {"name": "Group 1"}//$data has a similar set of values as the input
     *  */

    public function createWorkflowEmployeeGroup($workflowEmployeeGroup)
    {
        try {
             
            if(isset($workflowEmployeeGroup['context'])){
                $workflowEmployeeGroup['contextId']=$workflowEmployeeGroup['context'];

            }

            $validationResponse = ModelValidator::validate($this->workflowEmployeeGroupModel, $workflowEmployeeGroup, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('workflowEmployeeGroupMessages.basic.ERR_CREATE'), $validationResponse);
            }
            if(isset( $workflowEmployeeGroup['jobTitles'])){
                $workflowEmployeeGroup['jobTitles']=json_encode($workflowEmployeeGroup['jobTitles']);

            }
            if(isset( $workflowEmployeeGroup['employmentStatuses'])){
                $workflowEmployeeGroup['employmentStatuses']=json_encode($workflowEmployeeGroup['employmentStatuses']);

            }
            if(isset( $workflowEmployeeGroup['locations'])){
                $workflowEmployeeGroup['locations']=json_encode($workflowEmployeeGroup['locations']);

            }
            if(isset( $workflowEmployeeGroup['departments'])){
                $workflowEmployeeGroup['departments']=json_encode($workflowEmployeeGroup['departments']);

            }
            if(isset( $workflowEmployeeGroup['divisions'])){
                $workflowEmployeeGroup['divisions']=json_encode($workflowEmployeeGroup['divisions']);

            }
            if(isset( $workflowEmployeeGroup['reportingPersons'])){
                $workflowEmployeeGroup['reportingPersons']=json_encode($workflowEmployeeGroup['reportingPersons']);

            }
            if(isset( $workflowEmployeeGroup['name'])){
                $workflowEmployeeGroup['name']=$workflowEmployeeGroup['name'];

            }
            if(isset( $workflowEmployeeGroup['comment'])){
                $workflowEmployeeGroup['comment']=$workflowEmployeeGroup['comment'];

            }
            
            $newWorkflowEmployeeGroup = $this->store->insert($this->workflowEmployeeGroupModel, $workflowEmployeeGroup, true);

            return $this->success(201, Lang::get('workflowEmployeeGroupMessages.basic.SUCC_CREATE'), $newWorkflowEmployeeGroup);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowEmployeeGroupMessages.basic.ERR_CREATE'), null);
        }
    }


    /** 
     * Following function retrives all Workflow Employee Groups.
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "WorkflowEmployeeGroup retrived Successuflly",
     *      $data => {{"id": 1, name": "Group 1"}, {"id": 1, name": "Group 2"}}
     * ] 
     */
    public function getAllWorkflowEmployeeGroups($permittedFields, $options)
    {
        try {
            $filteredWorkflowEmployeeGroups = $this->store->getAll(
                $this->workflowEmployeeGroupModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]);

            if (!empty($filteredWorkflowEmployeeGroups['data'])) {
                foreach ($filteredWorkflowEmployeeGroups['data'] as $key => $value) {
                    $value = (array) $value;
                    $filteredWorkflowEmployeeGroups['data'][$key]->jobTitles = (!empty($value['jobTitles'])) ? json_decode($value['jobTitles']) : [];
                    $filteredWorkflowEmployeeGroups['data'][$key]->employmentStatuses = (!empty($value['employmentStatuses'])) ? json_decode($value['employmentStatuses']) : [];
                    $filteredWorkflowEmployeeGroups['data'][$key]->locations = (!empty($value['locations'])) ? json_decode($value['locations']) : [];
                    $filteredWorkflowEmployeeGroups['data'][$key]->departments = (!empty($value['departments'])) ? json_decode($value['departments']) : [];
                    $filteredWorkflowEmployeeGroups['data'][$key]->divisions = (!empty($value['divisions'])) ? json_decode($value['divisions']) : [];
                    $filteredWorkflowEmployeeGroups['data'][$key]->reportingPersons = (!empty($value['reportingPersons'])) ? json_decode($value['reportingPersons']) : [];
                }
            }

            return $this->success(200, Lang::get('workflowEmployeeGroupMessages.basic.SUCC_ALL_RETRIVE'), $filteredWorkflowEmployeeGroups);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowEmployeeGroupMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    

    /**
     * Following function updates a WorkflowEmployeeGroup.
     * 
     * @param $id WorkflowEmployeeGroup id
     * @param $WorkflowEmployeeGroup array containing WorkflowEmployeeGroup data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "WorkflowEmployeeGroup updated successfully.",
     *      $data => {"id": 1, actionName": "Relative"} // has a similar set of data as entered to updating WorkflowEmployeeGroup.
     * 
     */
    public function updateWorkflowEmployeeGroup($id, $workflowEmployeeGroup)
    {
        try {
               
            if(isset($workflowEmployeeGroup['context'])){
                $workflowEmployeeGroup['contextId']=$workflowEmployeeGroup['context'];
            }

            $validationResponse = ModelValidator::validate($this->workflowEmployeeGroupModel, $workflowEmployeeGroup, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('workflowEmployeeGroupMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $workflowEmployeeGroup['jobTitles']= (isset( $workflowEmployeeGroup['jobTitles'])) ? json_encode($workflowEmployeeGroup['jobTitles']) : json_encode([]);
            $workflowEmployeeGroup['employmentStatuses']= (isset( $workflowEmployeeGroup['employmentStatuses'])) ? json_encode($workflowEmployeeGroup['employmentStatuses']) : json_encode([]);
            $workflowEmployeeGroup['locations']=(isset( $workflowEmployeeGroup['locations'])) ? json_encode($workflowEmployeeGroup['locations']) : json_encode([]);
            $workflowEmployeeGroup['departments']=(isset( $workflowEmployeeGroup['departments'])) ? json_encode($workflowEmployeeGroup['departments']) : json_encode([]);
            $workflowEmployeeGroup['divisions']= (isset( $workflowEmployeeGroup['divisions'])) ? json_encode($workflowEmployeeGroup['divisions']) : json_encode([]);
            $workflowEmployeeGroup['reportingPersons']=(isset( $workflowEmployeeGroup['reportingPersons'])) ? json_encode($workflowEmployeeGroup['reportingPersons']) : json_encode([]);


            if(isset( $workflowEmployeeGroup['name'])){
                $workflowEmployeeGroup['name']=$workflowEmployeeGroup['name'];

            }
            if(isset( $workflowEmployeeGroup['comment'])){
                $workflowEmployeeGroup['comment']=$workflowEmployeeGroup['comment'];

            }
            
            $dbWorkflowEmpGroup = $this->store->getById($this->workflowEmployeeGroupModel, $id);
            if (is_null($dbWorkflowEmpGroup)) {
                return $this->error(404, Lang::get('workflowEmployeeGroupMessages.basic.ERR_NONEXISTENT_TERMINATION_REASON'), null);
            }

            $result = $this->store->updateById($this->workflowEmployeeGroupModel, $id, $workflowEmployeeGroup);

            if (!$result) {
                return $this->error(502, Lang::get('workflowEmployeeGroupMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('workflowEmployeeGroupMessages.basic.SUCC_UPDATE'), $workflowEmployeeGroup);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowEmployeeGroupMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     * 
     * @param $id WorkflowEmployeeGroup id
     * @param $WorkflowDefine array containing WorkflowEmployeeGroup data
     * @return int | String | array
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "WorkflowEmployeeGroup deleted successfully.",
     *      $data => null
     * 
     */
    public function deleteWorkflowEmployeeGroup($id)
    {
        try {
            
            $dbWorkflowEmpGroup = $this->store->getById($this->workflowEmployeeGroupModel, $id);
            if (is_null($dbWorkflowEmpGroup)) {
                return $this->error(404, Lang::get('workflowEmployeeGroupMessages.basic.ERR_NONEXISTENT_TERMINATION_REASON'), null);
            }
            //need to check the particular group link with any workflow
            $relatedWorkflowsCount = $this->store->getFacade()::table('workflowDefine')->where('employeeGroupId', $id)->where('isDelete', false)->count();
            if ($relatedWorkflowsCount > 0) {
                return $this->error(403, Lang::get('workflowEmployeeGroupMessages.basic.ERR_HAS_LINKED_WORKFLOWS'), null);
            }

            $this->store->getFacade()::table('workflowEmployeeGroup')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('workflowEmployeeGroupMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workflowEmployeeGroupMessages.basic.ERR_DELETE'), null);
        }
    }

   
}