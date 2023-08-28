<?php

namespace App\Services;

use Log;
use Exception;
use App\Library\Store;
use App\Library\Util;
use Illuminate\Support\Facades\Lang;
use App\Library\ModelValidator;
use App\Traits\JsonModelReader;
use App\Library\FileStore;
use App\Library\Session;
use App\Traits\FormTemplateInstanceHelper;
use Illuminate\Support\Facades\DB;
use DateTime;
use DateTimeZone;
use Carbon\Carbon;
use App\Traits\EmployeeHelper;
use App\Traits\ConfigHelper;
use App\Traits\LeaveAccrual;

/**
 * Name: EmployeeJourneyService
 * Purpose: Performs tasks related to the User Role model.
 * Description: User Role Service class is called by the EmployeeJourneyController where the requests related
 * to User Role Model (CRUD operations and others).
 * Module Creator: Hashan
 */
class EmployeeJourneyService extends BaseService
{
    use JsonModelReader;
    use FormTemplateInstanceHelper;
    use EmployeeHelper;
    use ConfigHelper;
    use LeaveAccrual;

    private $store;

    private $employeeJobModel;

    protected $employeeService;
    // private $uploadFileSize = 2097152;
    private $uploadFileSize = 3145728;
    private $fileStore;
    private $session;
    private $workflowService;
    private $autoGenerateIdService;

    public function __construct(Store $store, EmployeeService $employeeService, FileStore $fileStore, Session $session, WorkflowService $workflowService, AutoGenerateIdService $autoGenerateIdService)
    {
        $this->store = $store;
        $this->employeeJobModel = $this->getModel('employeeJob', true);
        $this->employeeService  = $employeeService;
        $this->fileStore = $fileStore;
        $this->session = $session;
        $this->workflowService = $workflowService;
        $this->autoGenerateIdService  = $autoGenerateIdService;
    }

    /**
     * Following function creates a employee journey record
     *
     * @param $employeeId employee Id
     * @param $employeeJourneyType enum ('PROMOTIONS','CONFIRMATION_CONTRACTS','TRANSFERS','RESIGNATIONS')
     * @param $data array containing the employee journey data
     * @return int | String | array
     *
     **/
    public function createEmployeeJourneyEvent($employeeId, $employeeJourneyType, $data)
    {
        try {
            $data['employeeId'] = $employeeId;
            $data['employeeJourneyType'] = $employeeJourneyType;


            $employeeDataObj = DB::table('employee')
            ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
            ->where('employee.id', '=', $employeeId)
            ->where('employee.isDelete', '=', 0)
            ->first();

            //get employementStatus
            $employmentStatusId = empty($employeeDataObj->employmentStatusId) ? null : $employeeDataObj->employmentStatusId;
            $employmentStatusDetail = null;
            // ignore employee if location not exist
            if (!empty($employmentStatusId)) {
                
                //get employementStatus
                $employmentStatusDetail = DB::table('employmentStatus')->where('id', $employmentStatusId)->first();
            }


            // $recentRecord = $this->store->getFacade()::table('employeeJob')
            //     ->where('employeeId', $employeeId)
            //     ->where('effectiveDate', '<=', $data['effectiveDate'])
            //     ->orderBy('effectiveDate', 'desc')
            //     ->orderBy('updatedAt', 'desc')
            //     ->first();
            // $recentRecord = (array) $recentRecord;
            // unset($recentRecord["id"]);
            // $data = array_merge($recentRecord, $data);

            $company = (array) $this->session->getCompany();
            $companyTimeZone = $company["timeZone"] ?? null;
            $companyDateObject = new DateTime("now", new DateTimeZone($companyTimeZone));
            $companyDate = $companyDateObject->format('Y-m-d');
            $upcomingJobs = $this->store->getFacade()::table('employeeJob')
                ->where('employeeId', $employeeId)
                ->where('employeeJourneyType', $employeeJourneyType)
                ->where('isRollback', false)
                ->where('effectiveDate', '>', $companyDate)
                ->get();

            if (!$upcomingJobs->isEmpty()) {
                switch ($employeeJourneyType) {
                    case 'PROMOTIONS':
                        return $this->error(400, Lang::get('employeeJourneyMessages.basic.ERR_UPCOMING_PROMOTION_EXISTENT'), null);
                    case 'CONFIRMATION_CONTRACTS':
                        return $this->error(400, Lang::get('employeeJourneyMessages.basic.ERR_UPCOMING_RENEWAL_EXISTENT'), null);
                    case 'TRANSFERS':
                        return $this->error(400, Lang::get('employeeJourneyMessages.basic.ERR_UPCOMING_TRANSFER_EXISTENT'), null);
                    case 'RESIGNATIONS':
                        return $this->error(400, Lang::get('employeeJourneyMessages.basic.ERR_UPCOMING_RESIGNATION_EXISTENT'), null);
                    case 'REJOINED':
                        return $this->error(400, Lang::get('employeeJourneyMessages.basic.ERR_UPCOMING_REJOINED_EXISTENT'), null);
                    case 'REACTIVATED':
                        return $this->error(400, Lang::get('employeeJourneyMessages.basic.ERR_UPCOMING_REACTIVATED_EXISTENT'), null);
                    default:
                        return $this->error(400, Lang::get('employeeJourneyMessages.basic.ERR_UPCOMING_JOB_EXISTENT'), null);
                }
            }

            $employee = $this->employeeService->getEmployee($employeeId)['data'];
            if (!$employee) {
                return $this->error(400, Lang::get('employeeJourneyMessages.basic.ERR_NONEXISTENT'), null);
            }

            if (isset($data['fileType'])) {
                $validFileFormats = [
                    "image/jpeg",
                    "application/pdf",
                ];

                if (!in_array($data['fileType'], $validFileFormats)) {
                    return $this->error(400, Lang::get('employeeJourneyMessages.basic.ERR_UPLOAD_TYPE'), null);
                }

                if ($data['fileSize'] > $this->uploadFileSize) {
                    return $this->error(400, Lang::get('employeeJourneyMessages.basic.ERR_UPLOAD_SIZE'), null);
                }

                $file = $this->fileStore->putBase64EncodedObject(
                    $data['fileName'],
                    $data['fileSize'],
                    $data['data']
                );

                $data['attachmentId'] = $file->id;
            }

            $recentRecord = (array) $this->store->getFacade()::table('employeeJob')
                ->where('id', $employee->currentJobsId)
                ->first();

            unset($recentRecord["id"]);
            $data = array_merge($recentRecord, $data);
            $data['previousRecordId'] = $employee->currentJobsId;

            $validationResponse = ModelValidator::validate($this->employeeJobModel, $data);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('employeeJourneyMessages.basic.ERR_VALIDATION'), $validationResponse);
            }


            $previousJobRecord = $this->store->getFacade()::table('employeeJob')
                ->select('employeeJob.*', 'employmentStatus.category')
                ->leftJoin('employmentStatus', 'employmentStatus.id', '=', 'employeeJob.employmentStatusId')
                ->where('employeeJob.id', $employee->currentJobsId)
                ->first();

            $this->store->getFacade()::beginTransaction();
            $employeeRelatedLeaveTypeAccrualDetails = [];
            $midYearPermentAccrueConfigs = (array) $this->getConfigValue('leave-accrue-config-for-emp-added-to-permenent-carder-in-mid-of-the-year');

            if ($employeeJourneyType == 'CONFIRMATION_CONTRACTS' && $data['confirmationAction'] == 'ABSORB_TO_PERMANENT_CARDER') {
               
                foreach ($midYearPermentAccrueConfigs['leaveTypes'] as $leaveTypeKey => $leaveTypeID) {
                    $relatedMonthlyAccrual = null;
                    $hasRelatedMonthlyAccrual = false;
                    $hasRelatedAnnualAccrual = false;
                    $typeKey = 'leaveType-'.$leaveTypeID;

                    $employeeRelatedLeaveTypeAccrualDetails[$typeKey]['hasRelatedMonthlyAccrual'] = false;
                    $employeeRelatedLeaveTypeAccrualDetails[$typeKey]['relatedMonthlyAccrual'] = [];


                    $leaveRelatedMothlyAccruals =  DB::table('leaveAccrual')
                    ->join('leaveType', 'leaveType.id', '=', 'leaveAccrual.leaveTypeId')
                    ->where('leaveType.id', '=', $leaveTypeID)
                    ->where('leaveType.isDelete', '=', 0)
                    ->where('leaveAccrual.accrualFrequency', '=', 'MONTHLY')
                    ->get(['leaveAccrual.*', 'leaveType.leavePeriod']);

                    foreach ($leaveRelatedMothlyAccruals as $leaveRelatedMothlyAccrual) {        
                        // get group employees
                        $employees = $this->getEmployeeIdsByLeaveGroupId($leaveRelatedMothlyAccrual->leaveEmployeeGroupId);
        
                        $selectedEmployee = $employees->first(function ($employee) use ($employeeId) {
                            return $employee->id == $employeeId;
                        });

                        if (!empty($selectedEmployee)) {
                            $relatedMonthlyAccrual = $leaveRelatedMothlyAccrual;
                            $employeeRelatedLeaveTypeAccrualDetails[$typeKey]['hasRelatedMonthlyAccrual'] = true;
                            $employeeRelatedLeaveTypeAccrualDetails[$typeKey]['relatedMonthlyAccrual'] = $relatedMonthlyAccrual;

                            $hasRelatedMonthlyAccrual = true;
                            break;
                        }
                    }   
                }
            }



            $result = $this->employeeService->createEmployeeMultiRecord($employeeId, 'jobs', (array) $data);

            if ($result['error']) {
                return $result;
            }

            if ($employeeJourneyType == 'CONFIRMATION_CONTRACTS' && $data['confirmationAction'] == 'ABSORB_TO_PERMANENT_CARDER') {

                foreach ($midYearPermentAccrueConfigs['leaveTypes'] as $leaveTypeKey => $leaveTypeID) {
                    $relatedAnnualAccrual = null;
                    $hasRelatedAnnualAccrual = false;
                    $typeKey = 'leaveType-'.$leaveTypeID;

                    $employeeRelatedLeaveTypeAccrualDetails[$typeKey]['leaveTypeID'] = $leaveTypeID;
                    $employeeRelatedLeaveTypeAccrualDetails[$typeKey]['hasRelatedAnnualAccrual'] = false;
                    $employeeRelatedLeaveTypeAccrualDetails[$typeKey]['relatedAnnualAccrual'] = [];

                    $leaveRelatedAnnualAccruals =  DB::table('leaveAccrual')
                        ->join('leaveType', 'leaveType.id', '=', 'leaveAccrual.leaveTypeId')
                        ->where('leaveType.id', '=', $leaveTypeID)
                        ->where('leaveType.isDelete', '=', 0)
                        ->where('leaveAccrual.accrualFrequency', '=', 'ANNUAL')
                        ->get(['leaveAccrual.*', 'leaveType.leavePeriod']);

                    foreach ($leaveRelatedAnnualAccruals as $leaveRelatedAnnualAccrual) {
                        // get group employees
                        // $employees = $this->getEmployeeIdsByLeaveGroupId($leaveRelatedAnnualAccrual->leaveEmployeeGroupId);
        
                        // $selectedAnnualEmployee = $employees->first(function ($employee) use ($employeeId) {
                        //     return $employee->id == $employeeId;
                        // });

                        if (!empty($leaveRelatedAnnualAccrual)) {
                            $relatedAnnualAccrual = $leaveRelatedAnnualAccrual;
                            $employeeRelatedLeaveTypeAccrualDetails[$typeKey]['hasRelatedAnnualAccrual'] = true;
                            $employeeRelatedLeaveTypeAccrualDetails[$typeKey]['relatedAnnualAccrual'] = $relatedAnnualAccrual;

                            $hasRelatedAnnualAccrual = true;
                            break;
                        }
                    }
                }
            }

    
            if ($employeeJourneyType == 'CONFIRMATION_CONTRACTS' && $data['confirmationAction'] == 'ABSORB_TO_PERMANENT_CARDER') { 


                $isAllowToAllocateLeavesForRestOfTheYear = true;
                //check whether previous job employmentStatusCategory is probation
                if ($previousJobRecord->category == 'PROBATION') {
                    $recentHireDateObj = Carbon::parse($employeeDataObj->recentHireDate);
                    $newJobEffectiveDateObj = Carbon::parse($data['effectiveDate']);
                    $recentHiredYear = $recentHireDateObj->copy()->format('Y');
                    $newJobEffectiveDateYear = $newJobEffectiveDateObj->copy()->format('Y');

                    if ($recentHiredYear !== $newJobEffectiveDateYear) {
                        $isAllowToAllocateLeavesForRestOfTheYear = false;
                    }
                }

                if ($isAllowToAllocateLeavesForRestOfTheYear) {
                    foreach ($employeeRelatedLeaveTypeAccrualDetails as $accrualLeaveTypekey => $leaveAcrrualConfigData) {
                        $leaveAcrrualConfigData = (array) $leaveAcrrualConfigData;
                        if($leaveAcrrualConfigData['hasRelatedMonthlyAccrual'] && $leaveAcrrualConfigData['hasRelatedAnnualAccrual']) {

                            if (!$leaveAcrrualConfigData['relatedMonthlyAccrual']->isAllowToAllocateAfterMidYearConfirm) {
                                continue;
                            }

                            //need to find the first accrual date of the related annual frequency config
    
                            if ($leaveAcrrualConfigData['relatedAnnualAccrual']->leavePeriod == 'STANDARD') {
                                $permenentEffectiveDate = $data['effectiveDate'];
                                $permenentEffectiveDateObj = Carbon::createFromFormat('Y-m-d', $permenentEffectiveDate, $companyTimeZone);
                                $permenentMonth = $permenentEffectiveDateObj->isoFormat('M');
                                $permenentFullMonth = $permenentEffectiveDateObj->isoFormat('MM');
                                $permenentDay = $permenentEffectiveDateObj->isoFormat('DD');
                                $permenentYear = $permenentEffectiveDateObj->format('Y');
                                $frequencyRule = $leaveAcrrualConfigData['relatedAnnualAccrual']->dayOfCreditingForAnnualFrequency;
                                $creditingFrequency = $midYearPermentAccrueConfigs['dayOfCreditingForMonthlyFrequency'];
                                $firstAccrualFrequency = $midYearPermentAccrueConfigs['firstAccrualForMonthlyFrequency'];
    
    
                                $annualAccrualDayOfPermenentYear = $permenentYear.'-'.$frequencyRule;
                                $annualAccrualDayOfPermenentYearObj = Carbon::createFromFormat('Y-m-d', $annualAccrualDayOfPermenentYear);
    
                                // if (!$annualAccrualDayOfPermenentYearObj->isSameDay($permenentEffectiveDateObj) && !$annualAccrualDayOfPermenentYearObj->greaterThan($permenentEffectiveDateObj)) {
                                // } 
                                $firstAccrualDateObj = $annualAccrualDayOfPermenentYearObj->copy()->addYear();
    
                                $dates = $this->getCreditingDates($data['effectiveDate'], $firstAccrualDateObj->format('Y-m-d'), $creditingFrequency, $firstAccrualFrequency, $leaveAcrrualConfigData['relatedMonthlyAccrual']);
    
                                foreach ($dates as $date) {
                                    // create date onjrct
                                    $dateObject = Carbon::createFromFormat('Y-m-d', $date, $companyTimeZone);
                                    $entitlement = null;
                                    if ($dateObject->greaterThanOrEqualTo($permenentEffectiveDateObj) && $dateObject->lessThan($firstAccrualDateObj)) {
                                        
                                        $leavePeriod['from'] = $permenentEffectiveDate;
                                        $leavePeriod['to'] = $firstAccrualDateObj->copy()->subDay()->format('Y-m-d');
    
                                        $leaveValidityPeriod = $this->leaveValidityPeriod($dateObject, $leavePeriod, $midYearPermentAccrueConfigs['accrualValidFrom']);
                                        
                                        $entitlement = [
                                            'employeeId' => $employeeId,
                                            'leaveTypeId' => $leaveAcrrualConfigData['leaveTypeID'],
                                            'leavePeriodFrom' => $leavePeriod['from'],
                                            'leavePeriodTo' => $leavePeriod['to'],
                                            'validFrom' => $leaveValidityPeriod['from'],
                                            'validTo' => $leaveValidityPeriod['to'],
                                            'type' => 'ACCRUAL',
                                            'entilementCount' => $midYearPermentAccrueConfigs['monthlyAllocatedLeaveAmount'],
                                            'comment' => null,
                                        ];
                                    }
    
                                    $entitlementId = null;
                                    if (!is_null($entitlement)) {
                                        $entitlementId = DB::table('leaveEntitlement')->insertGetId($entitlement);
                                    }
                                }
                                
                            } 
                            // elseif ($leaveAcrrualConfigData['relatedAnnualAccrual']->leavePeriod == 'HIRE_DATE_BASED') {
                                
                            //     $permenentEffectiveDate = $data['effectiveDate'];
                            //     $permenentEffectiveDateObj = Carbon::createFromFormat('Y-m-d', $permenentEffectiveDate, $companyTimeZone);
                            //     $permenentMonth = $permenentEffectiveDateObj->isoFormat('M');
                            //     $permenentFullMonth = $permenentEffectiveDateObj->isoFormat('MM');
                            //     $permenentDay = $permenentEffectiveDateObj->isoFormat('DD');
                            //     $permenentYear = $permenentEffectiveDateObj->format('Y');
                            //     $frequencyRule = $leaveAcrrualConfigData['relatedAnnualAccrual']->dayOfCreditingForAnnualFrequency;
                            //     $creditingFrequency = $midYearPermentAccrueConfigs['dayOfCreditingForMonthlyFrequency'];
                            //     $firstAccrualFrequency = $midYearPermentAccrueConfigs['firstAccrualForMonthlyFrequency'];
                            //     $actualHireDate = $data['effectiveDate'];
                            //     $hireDateObj = Carbon::createFromFormat('Y-m-d', $actualHireDate);
    
                            //     // if ($employmentStatusDetail->category == 'CONTRACT') {
                            //     //     $hireDateObj = Carbon::createFromFormat('Y-m-d', $data['effectiveDate']);
                            //     // } 
    
                            //     $hireFullMonth = $hireDateObj->isoFormat('MM');
                            //     $hireDay = $hireDateObj->isoFormat('DD');
    
                            //     $combineHireDateMonth = $hireFullMonth.'-'.$hireDay;
                            //     $dueHireDateBaseAccrualDateObj = $this->getRelaventHireDateBaseAccrualDateForGivenYear(((int)$permenentYear+1), $hireDateObj);
    
                            //     error_log('***************************************');
                            //     error_log($dueHireDateBaseAccrualDateObj->format('Y-m-d'));
    
                            //     if (!$dueHireDateBaseAccrualDateObj->isSameDay($permenentEffectiveDateObj) && !$dueHireDateBaseAccrualDateObj->greaterThan($permenentEffectiveDateObj)) {
                            //         $firstAccrualDateObj = $dueHireDateBaseAccrualDateObj->copy()->addYear();
    
                            //         $dates = $this->getCreditingDates($data['effectiveDate'], $firstAccrualDateObj->format('Y-m-d'), $creditingFrequency, $firstAccrualFrequency);
    
    
                            //         foreach ($dates as $date) {
                            //             // create date onjrct
                            //             $dateObject = Carbon::createFromFormat('Y-m-d', $date, $companyTimeZone);
                            //             $entitlement = null;
                            //             if ($dateObject->greaterThanOrEqualTo($permenentEffectiveDateObj) && $dateObject->lessThan($firstAccrualDateObj)) {
                                            
                            //                 $leavePeriod['from'] = $permenentEffectiveDate;
                            //                 $leavePeriod['to'] = $firstAccrualDateObj->copy()->subDay()->format('Y-m-d');
    
                            //                 $leaveValidityPeriod = $this->leaveValidityPeriod($dateObject, $leavePeriod, $midYearPermentAccrueConfigs['accrualValidFrom']);
                                            
                            //                 $entitlement = [
                            //                     'employeeId' => $employeeId,
                            //                     'leaveTypeId' => $leaveAcrrualConfigData['leaveTypeID'],
                            //                     'leavePeriodFrom' => $leavePeriod['from'],
                            //                     'leavePeriodTo' => $leavePeriod['to'],
                            //                     'validFrom' => $leaveValidityPeriod['from'],
                            //                     'validTo' => $leaveValidityPeriod['to'],
                            //                     'type' => 'ACCRUAL',
                            //                     'entilementCount' => $midYearPermentAccrueConfigs['monthlyAllocatedLeaveAmount'],
                            //                     'comment' => null,
                            //                 ];
                            //             }
    
                            //             $entitlementId = null;
                            //             if (!is_null($entitlement)) {
                            //                 $entitlementId = DB::table('leaveEntitlement')->insertGetId($entitlement);
                            //             }
                            //         }
                            //     } 
    
                            // }
                        }
                    }
                }
            


            }

            $employeeJobId = $result['data']['id'];

            switch ($employeeJourneyType) {
                case 'RESIGNATIONS':
                    $employeeJobCategoryId = isset($recentRecord['jobCategoryId']) ? $recentRecord['jobCategoryId'] : null;
                    $employeeEntityId = isset($recentRecord['orgStructureEntityId']) ? $recentRecord['orgStructureEntityId'] : null;
                    $templateId = $this->getResignationTemplateId($employeeJobCategoryId, $employeeEntityId);
                    if (!empty($templateId)) {
                        $this->createJobFormTemplateInstance($employeeJobId, $templateId, $employeeId, $employeeId);
                    }
                    break;
                    // case 'CONFIRMATION_CONTRACTS':
                    //     $employeeJobCategoryId = isset($recentRecord['jobCategoryId']) ? $recentRecord['jobCategoryId'] : null;
                    //     $employemeeEmployemntTypeId = isset($recentRecord['employmentStatusId']) ? $recentRecord['employmentStatusId'] : null;
                    //     $employeeEntityId = isset($recentRecord['orgStructureEntityId']) ? $recentRecord['orgStructureEntityId'] : null;
                    //     $templateId = $this->getConfirmationTemplateId($employeeJobCategoryId, $employemeeEmployemntTypeId, $employeeEntityId);
                    //     if (!empty($templateId)) {
                    //         $this->createJobFormTemplateInstance($employeeJobId, $templateId, $employeeId, $employeeId);
                    //     }
                    //     break;
            }

            $this->store->getFacade()::commit();

            return $result;
        } catch (Exception $e) {
            $this->store->getFacade()::rollback();
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('employeeJourneyMessages.basic.ERR_CREATE'), null);
        }
    }

    public function getConfirmationTemplateId($employeeJobCategoryId, $employemeeEmployemntTypeId, $employeeEntityId)
    {
        if (empty($employeeJobCategoryId) || empty($employemeeEmployemntTypeId) || empty($employeeEntityId)) {
            return null;
        }
        // get confirmation processes
        $processes = $this->store->getFacade()::table('confirmationProcess')
            ->join('confirmationProcessJobCategories', 'confirmationProcess.id', '=', 'confirmationProcessJobCategories.confirmationProcessId')
            ->join('confirmationProcessEmploymentTypes', 'confirmationProcess.id', '=', 'confirmationProcessEmploymentTypes.confirmationProcessId')
            ->where('confirmationProcess.isDelete', false)
            ->where('confirmationProcessJobCategories.jobCategoryId', $employeeJobCategoryId)
            ->where('confirmationProcessEmploymentTypes.employmentTypeId', $employemeeEmployemntTypeId)
            ->get(['confirmationProcess.id', 'confirmationProcess.formTemplateId', 'confirmationProcess.orgEntityId']);

        // get org tree data
        $orgTree = $this->store->getFacade()::table('orgEntity')->where('orgEntity.isDelete', false)->get(['id', 'parentEntityId']);

        $nodeIds = $this->getAllNodeIds($orgTree, $employeeEntityId, []);

        foreach ($nodeIds as $nodeId) {
            $result = $processes->firstWhere('orgEntityId', $nodeId);
            if (!empty($result)) {
                return $result->formTemplateId;
            }
        }
        return null;
    }

    public function getResignationTemplateId($employeeJobCategoryId, $employeeEntityId)
    {
        if (empty($employeeJobCategoryId) || empty($employeeEntityId)) {
            return null;
        }
        // get resignation processes
        $processes = $this->store->getFacade()::table('resignationProcess')
            ->join('resignationProcessJobCategories', 'resignationProcess.id', '=', 'resignationProcessJobCategories.resignationProcessId')
            ->where('resignationProcess.isDelete', false)
            ->where('resignationProcessJobCategories.jobCategoryId', $employeeJobCategoryId)
            ->get(['resignationProcess.id', 'resignationProcess.formTemplateId', 'resignationProcess.orgEntityId']);

        // get org tree data
        $orgTree = $this->store->getFacade()::table('orgEntity')->where('orgEntity.isDelete', false)->get(['id', 'parentEntityId']);

        $nodeIds = $this->getAllNodeIds($orgTree, $employeeEntityId, []);

        foreach ($nodeIds as $nodeId) {
            $result = $processes->firstWhere('orgEntityId', $nodeId);
            if (!empty($result)) {
                return $result->formTemplateId;
            }
        }
        return null;
    }

    private function getAllNodeIds($collection, $entityId, $nodeIds)
    {
        $nodeIds[] = $entityId;

        $node = $collection->firstWhere('id', $entityId);
        if (empty($node)) {
            return $nodeIds;
        }
        $entityId = $node->parentEntityId;
        if (empty($entityId)) {
            return $nodeIds;
        }
        return $this->getAllNodeIds($collection, $entityId, $nodeIds);
    }

    public function createJobFormTemplateInstance($employeeJobId, $templateId, $employeeId, $authorizedEmployeeId)
    {
        $createdBy = $this->session->getUser()->id;
        $formInstanceId = $this->createFormTemplateInstance($templateId, $employeeId, $authorizedEmployeeId, $createdBy);
        $data = [
            'employeeJobId' => $employeeJobId,
            'formTemplateInstanceId' => $formInstanceId
        ];
        $this->store->getFacade()::table('employeeJobformTemplateInstance')->insert($data);
    }

    /**
     * Following function creates employee resignation request
     *
     * @param $employeeId employee Id
     * @param $employeeJourneyType enum ('PROMOTIONS','CONFIRMATION_CONTRACTS','TRANSFERS','RESIGNATIONS')
     * @param $data array containing the employee journey data
     * @return int | String | array
     *
     **/
    public function createEmployeeResignationRequest($data)
    {
        try {
            DB::beginTransaction();
            $isAllowWf = true;
            $employeeJourneyType = 'RESIGNATIONS';
            $employeeId = $this->session->getUser()->employeeId;
            $userId = $this->session->getUser()->id;
            $data['employeeId'] = $employeeId;
            $data['employeeJourneyType'] = $employeeJourneyType;
            $company = (array) $this->session->getCompany();
            $companyTimeZone = $company["timeZone"] ?? null;
            $companyDateObject = new DateTime("now", new DateTimeZone($companyTimeZone));
            $companyDate = $companyDateObject->format('Y-m-d');

            $upcomingJobs = $this->store->getFacade()::table('employeeJob')
                ->where('employeeId', $employeeId)
                ->where('employeeJourneyType', $employeeJourneyType)
                ->where('isRollback', false)
                ->where('effectiveDate', '>', $companyDate)
                ->get();

            if (!$upcomingJobs->isEmpty()) {
                DB::rollback();
                return $this->error(400, Lang::get('employeeJourneyMessages.basic.ERR_UPCOMING_RESIGNATION_EXISTENT'), null);
            }


            $employee = $this->employeeService->getEmployee($employeeId, true)['data'];
            if (!$employee) {
                DB::rollback();
                return $this->error(400, Lang::get('employeeJourneyMessages.basic.ERR_NONEXISTENT'), null);
            }

            if (isset($data['fileType'])) {
                $validFileFormats = [
                    "image/jpeg",
                    "application/pdf",
                ];

                if (!in_array($data['fileType'], $validFileFormats)) {
                    DB::rollback();
                    return $this->error(400, Lang::get('employeeJourneyMessages.basic.ERR_UPLOAD_TYPE'), null);
                }

                if ($data['fileSize'] > $this->uploadFileSize) {
                    DB::rollback();
                    return $this->error(400, Lang::get('employeeJourneyMessages.basic.ERR_UPLOAD_SIZE'), null);
                }

                $file = $this->fileStore->putBase64EncodedObject(
                    $data['fileName'],
                    $data['fileSize'],
                    $data['data']
                );

                $data['attachmentId'] = $file->id;

                unset($data['attachDocument']);
                unset($data['fileType']);
                unset($data['fileSize']);
                unset($data['fileName']);
                unset($data['data']);
            }

            $recentRecord = (array) $this->store->getFacade()::table('employeeJob')
                ->where('id', $employee->currentJobsId)
                ->first();

            unset($recentRecord["id"]);
            $data = array_merge($recentRecord, $data);
            $data['previousRecordId'] = $employee->currentJobsId;

            $validationResponse = ModelValidator::validate($this->employeeJobModel, $data);

            if (!empty($validationResponse)) {
                DB::rollback();
                return $this->error(400, Lang::get('employeeJourneyMessages.basic.ERR_VALIDATION'), $validationResponse);
            }

            if ($isAllowWf) {
                // this is the workflow context id related for Resignation
                $context = 7;

                $selectedWorkflow = $this->workflowService->filterRelatedWorkflow($context, $employeeId);
                if (isset($selectedWorkflow['error']) && $selectedWorkflow['error']) {
                    DB::rollback();
                    return $this->error($selectedWorkflow['statusCode'], $selectedWorkflow['message'], null);
                }

                $workflowDefineId = $selectedWorkflow;

                $workFlowDefineData = DB::table('workflowDefine')
                    ->select('workflowDefine.id', 'workflowDefine.contextId')
                    ->leftJoin('workflowContext', 'workflowDefine.contextId', '=', 'workflowContext.id')
                    ->where('workflowContext.id', '=', $context)
                    ->where('workflowDefine.id', '=', $workflowDefineId)
                    ->where('workflowDefine.isDelete', '=', false)
                    ->first();

                $data['effectiveDateChangeHistory'] = [];
                $data['updatedEffectiveDate'] = $data['effectiveDate'];
                $data['updatedAt'] = Carbon::now()->toDateTimeString();
                $data['createdAt'] = Carbon::now()->toDateTimeString();
                $data['createdBy'] = $userId;
                $data['updatedBy'] = $userId;

                $finalStates = [];

                //check whether perticular employee have pending or inprogress resignation requests
                $relateRequestCount = $this->checkWhetherHasInprogressWorkflowRequests($employeeId, $data, $finalStates, $context);

                if ($relateRequestCount) {
                    DB::rollback();
                    return $this->error('500', Lang::get('employeeJourneyMessages.basic.ERR_HAS_REMAINING_INPROGRESS_OR_PRNDING_WF'), $workflowDefineId);
                }

                // send this resignation request through workflow process
                $workflowInstanceRes = $this->workflowService->runWorkflowProcess($workflowDefineId, $data, $employeeId);
                if ($workflowInstanceRes['error']) {
                    DB::rollback();
                    return $this->error($workflowInstanceRes['statusCode'], $workflowInstanceRes['message'], $workflowDefineId);
                }
            }
            DB::commit();
            return $this->success(201, Lang::get('employeeJourneyMessages.basic.SUCC_RESIGNATION_REQUEST_CREATE'), $data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('employeeJourneyMessages.basic.ERR_RESIGNATION_REQUEST_CREATE'), null);
        }
    }

    public function getResignationAttachment($fileId)
    {
        try {
            if (is_null($fileId)) {
                return $this->error(400, Lang::get('employeeJourneyMessages.basic.ERR_INVALID_REQUEST'), null);
            }

            $file = $this->fileStore->getBase64EncodedObject($fileId);

            return $this->success(200, Lang::get('employeeJourneyMessages.basic.SUCC_GET'), [$file]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('employeeJourneyMessages.basic.ERR_GET'), null);
        }
    }

    /**
     * Following function creates a employee journey record
     *
     * @param $employeeId employee Id
     * @param $employeeJourneyType enum ('PROMOTIONS','CONFIRMATION_CONTRACTS','TRANSFERS','RESIGNATIONS')
     * @param $data array containing the employee journey data
     * @return int | String | array
     *
     **/
    public function updateCurrentJob($employeeId, $data, $scope)
    {
        try {
            $id = $data["id"];
            $validationResponse = ModelValidator::validate($this->employeeJobModel, $data, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('employeeJourneyMessages.basic.ERR_VALIDATION'), $validationResponse);
            }

            $existingEmployeeJob = $this->store->getFacade()::table('employeeJob')
                ->where('id', $id)
                ->where('employeeId', $employeeId)
                ->first();

            if (is_null($existingEmployeeJob)) {
                return $this->error(404, Lang::get('employeeJourneyMessages.basic.ERR_NONEXISTENT'), null);
            }

            $workflow = $scope == 'EMPLOYEE' ? true : false;

            return $this->employeeService->updateEmployeeMultiRecord($employeeId, 'jobs', $id, (array) $data, $workflow);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('employeeJourneyMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function retrives file object for a given file id.
     */
    public function getAttachment($fileId)
    {
        try {
            if (is_null($fileId)) {
                return $this->error(400, Lang::get('employeeJourneyMessages.basic.ERR_INVALID_REQUEST'), null);
            }

            $file = $this->fileStore->getBase64EncodedObject($fileId);

            return $this->success(200, Lang::get('employeeJourneyMessages.basic.SUCC_GET'), $file);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('employeeJourneyMessages.basic.ERR_GET'), null);
        }
    }

    /**
     * Following function update upcoming employee journey milestone.
     */
    public function reupdateUpcomingEmployeeJourneyMilestone($employeeId, $id, $data)
    {
        try {
            $validationResponse = ModelValidator::validate($this->employeeJobModel, $data, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('employeeJourneyMessages.basic.ERR_VALIDATION'), $validationResponse);
            }

            $existingEmployeeJob = $this->store->getFacade()::table('employeeJob')
                ->where('id', $id)
                ->where('employeeId', $employeeId)
                ->first();

            if (is_null($existingEmployeeJob)) {
                return $this->error(404, Lang::get('employeeJourneyMessages.basic.ERR_NONEXISTENT'), null);
            }

            return $this->employeeService->updateEmployeeMultiRecord($employeeId, 'jobs', $id, (array) $data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('employeeJourneyMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function rollback upcoming employee journey milestone.
     */
    public function rollbackUpcomingEmployeeJourneyMilestone($employeeId, $id, $data)
    {
        try {
            $existingEmployeeJob = $this->store->getFacade()::table('employeeJob')
                ->where('id', $id)
                ->where('employeeId', $employeeId)
                ->first();

            if (is_null($existingEmployeeJob)) {
                return $this->error(404, Lang::get('employeeJourneyMessages.basic.ERR_NONEXISTENT'), null);
            }

            $data = [
                'isRollback' => true,
                'rollbackReason' => $data['rollbackReason']
            ];

            return $this->employeeService->updateEmployeeMultiRecord($employeeId, 'jobs', $id, (array) $data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('employeeJourneyMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function get rejoin eligible list.
     */
    public function getRejoinEligibleList()
    {
        $eligibleList = $this->store->getFacade()::table('employee')
            ->join('employeeJob', 'employee.currentJobsId', '=', 'employeeJob.id')
            ->join('resignationType', 'employeeJob.resignationTypeId', '=', 'resignationType.id')
            ->where('employeeJob.employeeJourneyType', '=', 'RESIGNATIONS')
            ->where('resignationType.allowedToRehire', '=', true)
            ->havingRaw('resigned_duration >= reactivate_allowed_duration')
            ->selectRaw('
                employee.id AS employee_id,
                CONCAT_WS(" ", employee.firstName, employee.middleName, employee.lastName) AS employee_name,
                resignationType.name AS resignation_type_name,
                employeeJob.effectiveDate AS resigned_date,
                TIMESTAMPDIFF(Day, `employeeJob`.`effectiveDate`, CURDATE()) AS resigned_duration_days,
                CASE `resignationType`.`reactivateAllowedPeriodUnit`
                    WHEN "YEAR" THEN TIMESTAMPDIFF(Year, `employeeJob`.`effectiveDate`, CURDATE())
                    WHEN "MONTH" THEN TIMESTAMPDIFF(Month, `employeeJob`.`effectiveDate`, CURDATE())
                    ELSE TIMESTAMPDIFF(Day, `employeeJob`.`effectiveDate`, CURDATE())
                END AS resigned_duration,
                resignationType.reactivateAllowedPeriod AS reactivate_allowed_duration
            ')
            ->get();

        return $this->success(200, Lang::get('employeeJourneyMessages.basic.SUCC_GET_ALL_REJOIN_ELIGIBLE_LIST'), $eligibleList);
    }

    /**
     * Following function get reactive eligible list.
     */
    public function getReactiveEligibleList()
    {
        $eligibleList = $this->store->getFacade()::table('employee')
            ->join('employeeJob', 'employee.currentJobsId', '=', 'employeeJob.id')
            ->join('resignationType', 'employeeJob.resignationTypeId', '=', 'resignationType.id')
            ->where('employeeJob.employeeJourneyType', '=', 'RESIGNATIONS')
            ->where('resignationType.allowedToRehire', '=', true)
            ->havingRaw('resigned_duration < reactivate_allowed_duration')
            ->selectRaw('
                employee.id AS employee_id,
                CONCAT_WS(" ", employee.firstName, employee.middleName, employee.lastName) AS employee_name,
                resignationType.name AS resignation_type_name,
                employeeJob.effectiveDate AS resigned_date,
                TIMESTAMPDIFF(Day, `employeeJob`.`effectiveDate`, CURDATE()) AS resigned_duration_days,
                CASE `resignationType`.`reactivateAllowedPeriodUnit`
                    WHEN "YEAR" THEN TIMESTAMPDIFF(Year, `employeeJob`.`effectiveDate`, CURDATE())
                    WHEN "MONTH" THEN TIMESTAMPDIFF(Month, `employeeJob`.`effectiveDate`, CURDATE())
                    ELSE TIMESTAMPDIFF(Day, `employeeJob`.`effectiveDate`, CURDATE())
                END AS resigned_duration,
                resignationType.reactivateAllowedPeriod AS reactivate_allowed_duration
            ')
            ->get();

        return $this->success(200, Lang::get('employeeJourneyMessages.basic.SUCC_GET_ALL_REACTIVE_ELIGIBLE_LIST'), $eligibleList);
    }

    /**
     * Following function to rejoin employee.
     */
    public function rejoinEmployee($employeeId, $data)
    {
        $response = $this->createEmployeeJourneyEvent($employeeId, 'REJOINED', $data['changes']);

        if ($response['error']) return $response;

        return $this->success(200, Lang::get('employeeJourneyMessages.basic.SUCC_REJOINED'));
    }

    /**
     * Following function to reactive employee.
     */
    public function reactiveEmployee($employeeId, $data)
    {
        if ($data['isNewEmployeeNumber']) {
            $employee = $this->store->getFacade()::table('employee')
                ->where('id', $employeeId)
                ->first();
            $oldEmployeeNumber = $employee->employeeNumber;

            $entityId = isset($data["changes"]["orgStructureEntityId"]) ? $data["changes"]["orgStructureEntityId"] : null;
            $employeeNumberResponse = $this->generateEmployeeNumber($entityId);

            if ($employeeNumberResponse['error']) {
                return $this->error(400, $employeeNumberResponse['message'], null);
            }

            $newEmployeeNumber = $employeeNumberResponse['data']['employeeNumber'];
            $employeeNumberConfigId = $employeeNumberResponse['data']['numberConfigId'];

            $this->store->getFacade()::table('employee')
                ->where('id', $employeeId)
                ->update(['employeeNumber' => $newEmployeeNumber]);

            $this->incrementEmployeeNumber($employeeNumberConfigId);
            $data['changes']['reactiveComment'] = "Employee number changed from $oldEmployeeNumber to $newEmployeeNumber.";
        }

        $response = $this->createEmployeeJourneyEvent($employeeId, 'REACTIVATED', $data['changes']);

        if ($response['error']) return $response;

        return $this->success(200, Lang::get('employeeJourneyMessages.basic.SUCC_REACTIVATED'));
    }

    private function checkWhetherHasInprogressWorkflowRequests($employeeId, $draftData, $finalStates, $context)
    {
        $workFlowDetailsCount = DB::table('workflowInstance')
            ->select('workflowInstance.*', 'workflowDetail.employeeId', 'workflowDetail.details')
            ->leftJoin('workflowDefine', 'workflowDefine.id', '=', 'workflowInstance.workflowId')
            ->leftJoin('workflowDetail', 'workflowDetail.instanceId', '=', 'workflowInstance.id')
            ->where('workflowDefine.contextId', '=', $context)
            ->where('workflowDetail.employeeId', '=', $employeeId)
            ->where('workflowInstance.isDelete', '=', false)
            ->whereNotIn('workflowInstance.currentStateId', [2, 3, 4])
            ->count();


        if (!is_null($workFlowDetailsCount) && $workFlowDetailsCount > 0) {
            return true;
        }

        return false;
    }
}
