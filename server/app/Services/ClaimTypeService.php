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
use stdClass;
use App\Library\Session;
/**
 * Name: ClaimTypeService
 * Purpose: Performs tasks related to the ClaimType model.
 * Description: ClaimType Service class is called by the ClaimTypeController where the requests related
 * to ClaimType Model (basic operations and others). Table that is being modified is ClaimType.
 * Module Creator: Tharindu Darshana
 */
class ClaimTypeService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $claimTypeModel;
    private $claimTypePackageModel;
    private $claimAllocationDetailModel;
    private $bulkClaimAllocationHistoryModal;
    protected $session;

    public function __construct(Store $store, Session $session)
    {
        $this->store = $store;
        $this->session = $session;
        $this->claimTypeModel = $this->getModel('claimType', true);
        $this->claimTypePackageModel = $this->getModel('claimPackages', true);
        $this->claimAllocationDetailModel = $this->getModel('claimAllocationDetail', true);
        $this->bulkClaimAllocationHistoryModal = $this->getModel('bulkClaimAllocationHistory', true);
    }
    

    /**
     * Following function creates a claim type
     * 
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "claim type created Successuflly",
     * $data => {"name": "Group 1"}//$data has a similar set of values as the input
     *  */

    public function createClaimType($claimTypeData)
    {
        try {
            $validationResponse = ModelValidator::validate($this->claimTypeModel, $claimTypeData, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('financialYearMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $newClaimType = $this->store->insert($this->claimTypeModel, $claimTypeData, true);

            return $this->success(201, Lang::get('financialYearMessages.basic.SUCC_CREATE'), $newClaimType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_CREATE'), null);
        }
    }

    /**
     * Following function retrives claim type by provide id.
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Claim Type retrieved Successfully!",
     *      $data => {"title": "LK HR", ...}
     * ]
     */
    public function getClaimType($id)
    {
        try {
            $claimType = $this->store->getById($this->claimTypeModel, $id);
            return $this->success(200, Lang::get('leaveTypeMessages.basic.SUCC_GET'), $claimType);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('leaveTypeMessages.basic.ERR_GET'), null);
        }
    }


    /**
     * Following function creates a claim allocation.
     * 
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "Claim allocation created Successuflly",
     * $data => {"name": "Group 1"}//$data has a similar set of values as the input
     *  */

    public function addEmployeeClaimAllocation($claimAllocationDetail)
    {
        try {
            $validationResponse = ModelValidator::validate($this->claimAllocationDetailModel, $claimAllocationDetail, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('financialYearMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $newEmployeeClaimAllocation = $this->store->insert($this->claimAllocationDetailModel, $claimAllocationDetail, true);

            return $this->success(201, Lang::get('financialYearMessages.basic.SUCC_CREATE'), $newEmployeeClaimAllocation);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function creates bulk employee claim allocations.
     * 
     * 
     * Sample output:
     * $statusCode => 200,
     * $message => "Bulk Employee allocation created Successuflly",
     * $data => {"name": "Group 1"}//$data has a similar set of values as the input
     *  */

    public function addBulkEmployeeClaimAllocation($claimAllocationData)
    {
        DB::beginTransaction();
        try {
            
            $query = DB::table('employee')
                ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                ->leftJoin('employmentStatus', 'employmentStatus.id', '=', 'employeeJob.employmentStatusId');

            $jobTitles = !empty($claimAllocationData['selectedJobTitles']) ? $claimAllocationData['selectedJobTitles'] : [];
            if (!empty($jobTitles)) {
                $query->whereIn('employeeJob.jobTitleId', $jobTitles);
            }

            $employmentStatuses = !empty($claimAllocationData['selectedEmployStatuses']) ? $claimAllocationData['selectedEmployStatuses'] : [];
            if (!empty($employmentStatuses)) {
                $query->whereIn('employeeJob.employmentStatusId', $employmentStatuses);
            }

            $jobCategories = !empty($claimAllocationData['selectedJobCategories']) ? $claimAllocationData['selectedJobCategories'] : [];
            if (!empty($jobCategories)) {
                $query->whereIn('employeeJob.jobCategoryId', $jobCategories);
            }

            $filteredEmployees = $query->pluck('employee.id')->toArray();


            //get current allocated employees
            $currentClaimEmployees = DB::table('claimAllocationDetail')
                ->where('financialYearId', $claimAllocationData['financialYearId'])
                ->where('claimTypeId', $claimAllocationData['claimTypeId'])
                ->pluck('employeeId')->toArray();

            $newlyAddedClaimAllocations = [];
            $newlyAddedEmployeeIds = [];
            $alreadyExsistClaimAllocations = [];

            
            if (sizeof($filteredEmployees) > 0) {
                foreach ($filteredEmployees as $key => $employeeId) {
                    
                    //check employee has claim record 
                    if (!empty($currentClaimEmployees) && in_array($employeeId, $currentClaimEmployees)) {
                        array_push($alreadyExsistClaimAllocations, $employeeId);
                    } else {
                        array_push($newlyAddedEmployeeIds, $employeeId);
                        $newlyAddedClaimAllocations [] = [
                            'financialYearId' => $claimAllocationData['financialYearId'],
                            'claimTypeId' => $claimAllocationData['claimTypeId'],
                            'employeeId' => $employeeId,
                            'allocatedAmount' => $claimAllocationData['allocatedAmount'],
                            'usedAmount' => 0,
                        ];
                    }
                }

                if (sizeof($newlyAddedClaimAllocations) > 0) {
                    //save newly added allocations
                    $saveAloocations = DB::table('claimAllocationDetail')->insert($newlyAddedClaimAllocations);
                }


                if (sizeof($alreadyExsistClaimAllocations) > 0) {
                    //update exsist allocation
                    $updateAloocations = DB::table('claimAllocationDetail')
                        ->where('financialYearId', $claimAllocationData['financialYearId'])
                        ->where('claimTypeId', $claimAllocationData['claimTypeId'])
                        ->where('usedAmount', '<',$claimAllocationData['allocatedAmount'])
                        ->whereIn('employeeId', $alreadyExsistClaimAllocations)
                        ->update(["allocatedAmount" => $claimAllocationData['allocatedAmount']]);
                }
            } 

            //update history record for bulk allocation 
            $bulkClaimAllocationHistoryData = [
                'financialYearId' => $claimAllocationData['financialYearId'],
                'claimTypeId' => $claimAllocationData['claimTypeId'],
                'allocationType' => $claimAllocationData['allocationType'],
                'allocatedAmount' => $claimAllocationData['allocatedAmount'],
                'newlyAffectedEmployees' => $newlyAddedEmployeeIds,
                'updatedEmployees' => $alreadyExsistClaimAllocations,
            ];

            $validationResponse = ModelValidator::validate($this->bulkClaimAllocationHistoryModal, $bulkClaimAllocationHistoryData, false);
            if (!empty($validationResponse)) {
                DB::rollback();
                return $this->error(400, Lang::get('financialYearMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $bulkClaimAllocationHistoryData['newlyAffectedEmployees'] = json_encode($bulkClaimAllocationHistoryData['newlyAffectedEmployees']);
            $bulkClaimAllocationHistoryData['updatedEmployees'] = json_encode($bulkClaimAllocationHistoryData['updatedEmployees']);

            $historySave = $this->store->insert($this->bulkClaimAllocationHistoryModal, $bulkClaimAllocationHistoryData, true);

        
            DB::commit();
            return $this->success(201, Lang::get('financialYearMessages.basic.SUCC_CREATE'), []);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_CREATE'), null);
        }
    }


    /** 
     * Following function retrives all claim types.
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Claim types retrived Successuflly",
     *      $data => {{"id": 1, poolName": "Pool 1"}, {"id": 1, poolName": "Pool 2"}}
     * ] 
     */
    public function getAllClaimTypes($permittedFields, $options)
    {
        try {
            $filteredClaimTypes = $this->store->getAll(
                $this->claimTypeModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]);


            return $this->success(200, Lang::get('financialYearMessages.basic.SUCC_ALL_RETRIVE'), $filteredClaimTypes);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }


    /** 
     * Following function retrives all employee claim allocation list.
     * 
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Employee Claim Allocation list retrived Successuflly",
     *      $data => {{"id": 1, poolName": "Pool 1"}, {"id": 1, poolName": "Pool 2"}}
     * ] 
     */
    public function getEmployeeClaimAllocationList($request)
    {
        try {

            $pageNo = $request->query('pageNo', null);
            $pageCount = $request->query('pageCount', null);
            $selectedFinacialYear = $request->query('selectedFinacialYear', null);
            $selectedClaimType = $request->query('selectedClaimType', null);

            //get all employee ids that has record for selected financial year and claimType
            $employeeClaimDetails = DB::table('claimAllocationDetail')
                ->selectRaw(
                    'claimAllocationDetail.employeeId,
                    claimAllocationDetail.id,
                    claimAllocationDetail.claimTypeId,
                    claimAllocationDetail.financialYearId,
                    claimAllocationDetail.allocatedAmount,
                    claimAllocationDetail.usedAmount,
                    claimAllocationDetail.createdAt,
                    employee.firstName,
                    employee.lastName,
                    claimType.typeName,
                    financialYear.financialDateRangeString'
                )
                ->leftJoin('employee','employee.id',"=","claimAllocationDetail.employeeId")
                ->leftJoin('claimType','claimType.id',"=","claimAllocationDetail.claimTypeId")
                ->leftJoin('financialYear','financialYear.id',"=","claimAllocationDetail.financialYearId")
                ->where('claimAllocationDetail.financialYearId', '=', $selectedFinacialYear)
                ->where('claimAllocationDetail.claimTypeId', '=', $selectedClaimType);
                
                // ->groupBy("claimAllocationDetail.employeeId");

            $count = $employeeClaimDetails->count();

            $employeeClaimDetails = $employeeClaimDetails->orderBy('claimAllocationDetail.createdAt', 'DESC');

            if ($pageNo && $pageCount) {
                $skip = ($pageNo - 1) * $pageCount;
                $employeeClaimDetails = $employeeClaimDetails->skip($skip)->take($pageCount);
                $employeeClaimDetails = $employeeClaimDetails->skip($skip)->take($pageCount);
            }

            $responce = new stdClass();
            $responce->count = $count; 
            $responce->sheets = $employeeClaimDetails->get();
            $responce->success = true;


            return $this->success(200, Lang::get('financialYearMessages.basic.SUCC_ALL_RETRIVE'), $responce);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }


    /** 
     * Following function retrives allocation enable claim types.
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Claim types retrived Successuflly",
     *      $data => {{"id": 1, poolName": "Pool 1"}, {"id": 1, poolName": "Pool 2"}}
     * ] 
     */
    public function getAllocationEnableClaimTypes()
    {
        try {
            
            $filteredClaimTypes = $this->store->getFacade()::table('claimType')
            ->where('isDelete', false)
            ->where('isAllocationEnable', true)
            ->get();

            return $this->success(200, Lang::get('financialYearMessages.basic.SUCC_ALL_RETRIVE'), $filteredClaimTypes);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }


    /** 
     * Following function retrive employee claim allocation data
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Employee Claim Allocation record retrived Successuflly",
     *      $data => {{"id": 1, poolName": "Pool 1"}, {"id": 1, poolName": "Pool 2"}}
     * ] 
     */
    public function getEmployeeClaimAllocationData($request)
    {

        try {
            $financialYear = $request->query('financialYearId', null);
            $claimType = $request->query('claimType', null);
            $employeeId = $this->session->employee->id;

            $allocationData = $this->store->getFacade()::table('claimAllocationDetail')
                    ->where('employeeId', $employeeId)
                    ->where('financialYearId', $financialYear)
                    ->where('claimTypeId', $claimType)
                    ->first();

            return $this->success(200, Lang::get('financialYearMessages.basic.SUCC_ALL_RETRIVE'), $allocationData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_ALL_RETRIVE'), null);
        }

    }

    /** 
     * Following function retrives all employee eligible claim types for apply claim request
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Claim Types retrived Successuflly",
     *      $data => {{"id": 1, poolName": "Pool 1"}, {"id": 1, poolName": "Pool 2"}}
     * ] 
     */
    public function getEmployeeEligibleClaimTypes()
    {
        try {

            $employeeId = $this->session->employee->id;
            if (!empty($employeeId)) {
                $employee = $this->store->getFacade()::table('employee')
                    ->where('id', $employeeId)
                    ->first();
    
                if (!empty($employee->currentJobsId)) {
                    $employee->currentJob = $this->store->getFacade()::table('employeeJob')
                        ->where('id', $employee->currentJobsId)
                        ->first();
    
                    $employee->currentEmploymentStatus = $this->store->getFacade()::table('employmentStatus')
                        ->where('id', $employee->currentJob->employmentStatusId)
                        ->first();
                    $employee->currentEmployeJobCategory = $this->store->getFacade()::table('jobCategory')
                        ->where('id', $employee->currentJob->jobCategoryId)
                        ->first();
                } else {
                    $employee->currentJob = null;
                    $employee->currentEmploymentStatus = null;
                    $employee->currentEmployeJobCategory = null;
                }
            }


            //get package set without consider org
            $query = $this->store->getFacade()::table($this->claimTypePackageModel->getName());

            if ($employee->currentJob->employmentStatusId) {
                $query->where(function ($subQuery) use ($employee) {
                    $subQuery->whereJsonContains('allowEmploymentStatuses', $employee->currentJob->employmentStatusId)
                        ->orWhereJsonLength('allowEmploymentStatuses', 0);
                });
            } else {
                $query->where(function ($subQuery) use ($employee) {
                    $subQuery->whereJsonLength('allowEmploymentStatuses', 0);
                });
            }

            if ($employee->currentJob->jobCategoryId) {
                $query->where(function ($subQuery) use ($employee) {
                    $subQuery->whereJsonContains('allowJobCategories', $employee->currentJob->jobCategoryId)
                        ->orWhereJsonLength('allowJobCategories', 0);
                });
            } else {
                $query->where(function ($subQuery) use ($employee) {
                    $subQuery->whereJsonLength('allowJobCategories', 0);
                });
            }
            
            $packagesWithotOrgFiltering = $query->where('isDelete', false)->get();
            $eligibleClaimTypeIds = [];

            if (sizeof($packagesWithotOrgFiltering) > 0) {
                $orgEntities = $this->store->getFacade()::table("orgEntity")->where('isDelete', false)->get();
                foreach ($packagesWithotOrgFiltering as $key => $value) {
                    $value = (array) $value;
                    $packageClaimTypes = $value['allocatedClaimTypes'] ? json_decode($value['allocatedClaimTypes']) : [];
                    $packageOrgEntityId = $value['allowOrgEntityId'];
                    $packageEffectiveOrgEntities = $this->getParentEntityRelatedChildNodes((int)$packageOrgEntityId, $orgEntities);

                    array_push($packageEffectiveOrgEntities, $packageOrgEntityId);

                    if (in_array($employee->currentJob->orgStructureEntityId, $packageEffectiveOrgEntities)) {
                        if (sizeof($packageClaimTypes) > 0) {
                            $eligibleClaimTypeIds = array_merge($eligibleClaimTypeIds, $packageClaimTypes);
                        }
                    }
                }
            }

            $eligibleClaimTypes = $this->store->getFacade()::table($this->claimTypeModel->getName())->whereIn('id', $eligibleClaimTypeIds)->where('isDelete', false)->get();

            return $this->success(200, Lang::get('financialYearMessages.basic.SUCC_ALL_RETRIVE'), $eligibleClaimTypes);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /** 
     * Following function retrives all claim  types by provided org entity id
     * 
     * @return int | String | array
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Claim types retrived Successuflly",
     *      $data => {{"id": 1, poolName": "Pool 1"}, {"id": 1, poolName": "Pool 2"}}
     * ] 
     */
    public function getClaimTypesByEntityId($orgEntityId)
    {
        try {
           
            $orgEntities = $this->store->getFacade()::table("orgEntity")->where('isDelete', false)->get();
            $orgEntityIds = $this->getParentEntityRelatedChildNodes((int)$orgEntityId, $orgEntities);

            array_push($orgEntityIds, $orgEntityId);

            error_log(json_encode($orgEntityIds));

            $claimTypes = $this->store->getFacade()::table('claimType')
                ->whereIn('orgEntityId', $orgEntityIds)
                ->where('isDelete', false)
                ->get();

            return $this->success(200, Lang::get('financialYearMessages.basic.SUCC_ALL_RETRIVE'), $claimTypes);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    public function getParentEntityRelatedChildNodes($id, $items)
    {
        $kids = [];
        foreach ($items as $key => $item) {
            $item = (array) $item;
            if ($item['parentEntityId'] === $id) {
                $kids[] = $item['id'];
                array_push($kids, ...$this->getParentEntityRelatedChildNodes($item['id'], $items));
            }
        }
        return $kids;
    }

    

    /**
     * Following function updates claim type
     * 
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "Claim Type updated successfully.",
     *      $data => {"id": 1, actionName": "Relative"} // has a similar set of data as entered to updating Workflow Approver Pools.
     * 
     */
    public function updateClaimType($id, $claimTypeData)
    {
        try {
               
            $validationResponse = ModelValidator::validate($this->claimTypeModel, $claimTypeData, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('financialYearMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbClaimType = $this->store->getById($this->claimTypeModel, $id);
            if (is_null($dbClaimType)) {
                return $this->error(404, Lang::get('financialYearMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            $result = $this->store->updateById($this->claimTypeModel, $id, $claimTypeData);

            if (!$result) {
                return $this->error(502, Lang::get('financialYearMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('financialYearMessages.basic.SUCC_UPDATE'), $claimTypeData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_UPDATE'), null);
        }
    }


    /**
     * Following function updates a Employee Claim Allocation
     * 
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "Employee Claim allocation updated successfully.",
     *      $data => {"id": 1, actionName": "Relative"} // has a similar set of data as entered to updating Workflow Approver Pools.
     * 
     */
    public function updateEmployeeClaimAllocations($dataset)
    {
        try {
            $employeeClaimAllocationsData = json_decode($dataset['updatedClaimAllocations']);

            foreach ($employeeClaimAllocationsData as $key => $value) {
                $value = (array) $value;
                
                $claimAllocationRecord = $this->store->getById($this->claimAllocationDetailModel, $value['id']);
                if (is_null($claimAllocationRecord)) {
                    return $this->error(404, Lang::get('financialYearMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
                }

                $updatedRecord = [
                    'allocatedAmount' => $value['allocatedAmount']
                ];

                $result = $this->store->updateById($this->claimAllocationDetailModel, $value['id'], $updatedRecord);
                if (!$result) {
                    return $this->error(502, Lang::get('financialYearMessages.basic.ERR_UPDATE'), $id);
                }

            }
               
            return $this->success(200, Lang::get('financialYearMessages.basic.SUCC_UPDATE'), []);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function delete Claim Type.
     * 
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "Claim Type deleted successfully.",
     *      $data => null
     * 
     */
    public function deleteClaimType($id)
    {
        try {
        
            $dbClaimType = $this->store->getById($this->claimTypeModel, $id);
            if (is_null($dbClaimType)) {
                return $this->error(404, Lang::get('workflowEmployeeGroupMessages.basic.ERR_NONEXISTENT_TERMINATION_REASON'), null);
            }

            $hasLinkedPackeges = false;

            //check whether has any claim type link with claim package
            $relatedClaimPackageRecords = $this->store->getFacade()::table('claimPackages')
                ->where('isDelete', false)
                ->get();

            if (!is_null($relatedClaimPackageRecords)) {
                foreach ($relatedClaimPackageRecords as $key => $value) {
                    $value = (array) $value;
                    $allocatedClaimTypes = json_decode($value['allocatedClaimTypes']);

                    if (in_array($id, $allocatedClaimTypes)) {
                        $hasLinkedPackeges = true;
                        break;
                    }
                }
            }

            if ($hasLinkedPackeges) {
                return $this->error(502, Lang::get('financialYearMessages.basic.CLAIM_TYPE_DELETE_ERR_NOTALLOWED'), null);
            }

            $this->store->getFacade()::table('claimType')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('financialYearMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_DELETE'), null);
        }
    }


    /**
     * Following function delete Employee Claim Allocation
     * 
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "Employee claim allocation deleted successfully.",
     *      $data => null
     * 
     */
    public function deleteEmployeeClaimAllocation($id)
    {
        try {
        
            $dbClaimType = $this->store->getById($this->claimAllocationDetailModel, $id);
            if (is_null($dbClaimType)) {
                return $this->error(404, Lang::get('workflowEmployeeGroupMessages.basic.ERR_NONEXISTENT_TERMINATION_REASON'), null);
            }

            $this->store->getFacade()::table('claimAllocationDetail')->where('id', $id)->delete();

            return $this->success(200, Lang::get('financialYearMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('financialYearMessages.basic.ERR_DELETE'), null);
        }
    }

   
}