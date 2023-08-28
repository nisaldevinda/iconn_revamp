<?php

namespace App\Services;

use App\Library\Store;
use App\Traits\JsonModelReader;
use Maatwebsite\Excel\Facades\Excel;
use App\Library\ExcelProcessor;
use App\Library\LeaveExcelProcessor;
use App\Library\Session;
use \Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use App\Library\FileStore;
use Illuminate\Support\Facades\Log;
use App\Library\ModelValidator;
use Illuminate\Support\Facades\DB;
use App\Exceptions\Exception;
use App\Library\EmployeePromotionExcelProcessor;
use App\Library\EmployeeTransferExcelProcessor;
use App\Library\SalaryIncrementExcelProcessor;
use Carbon\Carbon;

/**
 * Name: BulkUpload
 * Purpose: Performs tasks related to the User Role model.
 * Description: User Role Service class is called by the CompanyController where the requests related
 * to User Role Model (CRUD operations and others).
 * Module Creator: Yohan
 */
class BulkUploadService extends BaseService
{
    use JsonModelReader;

    private $store;
    private $session;
    private $fileStore;
    private $employeeService;
    private $employeeJourneyService;
    private const CHUNK_THRESHOLD = 100;
    private $leaveEntitlementModel;
    private $employeeSalaryModel;
    private $salaryIncrementUploadHistoryModel;
    private $employeeJourneyUploadHistoryModel;

    public function __construct(Store $store,  Session $session, FileStore $fileStore, EmployeeService $employeeService, EmployeeJourneyService $employeeJourneyService)
    {
        $this->store = $store;
        $this->store = $store;
        $this->queryBuilder = $store->getFacade();
        $this->session = $session;
        $this->fileStore = $fileStore;
        $this->employeeService = $employeeService;
        $this->employeeJourneyService = $employeeJourneyService;
        $this->leaveEntitlementModel = $this->getModel('leaveEntitlement', true);
        $this->employeeSalaryModel = $this->getModel('employeeSalary', true);
        $this->salaryIncrementUploadHistoryModel = $this->getModel('salaryIncrementUploadHistory', true);
        $this->employeeJourneyUploadHistoryModel = $this->getModel('employeeJourneyUploadHistory', true);
    }

    /**
     * Following function retrives a single company for a provided company_id.
     *
     * @param $id user company id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Company retrieved Successfully!",
     *      $data => {"title": "LK HR", ...}
     * ]
     */
    public function downloadTemplate($data)
    {
        try {
            $dynamicModel = $this->getModel($data['modelName'], true);
            $downloadParams = [
                'defintionStrcutre' => ['basicInformation'],
                'feildCount' => $data['feildCount']
            ];
            $excelData = Excel::download(new ExcelProcessor($dynamicModel, $this->session, $this->store, $downloadParams), 'test.xlsx');
            $file = $excelData->getFile()->getPathname();
            $fileData = file_get_contents($file);
            unlink($file); // deleting file cache
            return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_GET_FILE'), base64_encode($fileData));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('bulkUploadMessages.basic.ERR_GET_FILE'), null);
        }
    }


    /**
     * Following function download leave upload template
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Excel template downloaded successfully!",
     * ]
     */
    public function downloadLeaveTemplate()
    {
        try {
            $dynamicModel = $this->getModel('leaveEntitlement', true);
            $downloadParams = [
                'defintionStrcutre' => ['basicInformation'],
                'feildCount' => 1
            ];

            //check whether uploadable leave types exsist
            $leaveTypesCount = $this->queryBuilder::table('leaveType')
                ->select('id', 'name')
                ->where('allowExceedingBalance', false)
                ->where('isDelete', false)
                ->count();

            if ($leaveTypesCount == 0) {
                return $this->error(500, Lang::get('bulkUploadMessages.basic.ERR_NO_ANY_UPLOADABLE_LEAVE_TYPE_EXSIST'), null);
            }

            $excelData = Excel::download(new LeaveExcelProcessor($dynamicModel, $this->session, $this->store, $downloadParams), 'test.xlsx');
            $file = $excelData->getFile()->getPathname();
            $fileData = file_get_contents($file);
            unlink($file); // deleting file cache
            return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_GET_FILE'), base64_encode($fileData));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('bulkUploadMessages.basic.ERR_GET_FILE'), null);
        }
    }

    /**
     * Following function upload leave data from excel file.
     *
     * @param $data array containing company data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Leave Data uploaded Successfully",
     *      $data => []
     *
     */
    public function uploadLeaveTemplate($data)
    {
        try {
            $dynamicModel = $this->getModel($data['modelName'], true);
            $excelManipulator = new LeaveExcelProcessor($dynamicModel, $this->session, $this->store);
            $destrcuturedFileName = explode('.', $data['fileName']);

            // code logic to decord the base64 file
            $fileData = base64_decode($data['file']);
            // $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString() . '.xls';
            if ($data['fileType'] === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString() . '.xlsx';
            }

            if ($data['fileType'] === 'application/vnd.ms-excel') {
                $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString() . '.xls';
            }

            file_put_contents($tmpFilePath, $fileData);

            Excel::import($excelManipulator, $tmpFilePath);
            $getProcessedLeaveEntitlement = $excelManipulator->getProcessedDataset();

            $validateData = $this->validateProcessedEntitltments($getProcessedLeaveEntitlement);

            $base64Uri = $data['fileType'] . ',' . base64_encode($fileData);
            $fileObject = [
                'fileName' => $destrcuturedFileName[0],
                'fileSize' => $data['fileSize'],
                'data' => $base64Uri
            ];
            $responseData = [
                "hasValidationErrors" => false,
                "errors" => [],
                "errorCount" => $validateData['errorCount'],
                "validatedData" => $validateData['data']
            ];
            return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_UPLOAD_FILE'), $responseData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('bulkUploadMessages.basic.ERR_UPLOAD_FILE'), null);
        }
    }


    /**
     * Following function save uploded leave data.
     *
     * @param $data array containing leave data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Successfully",
     *      $data => {"title": "LK HR", ...} // has a similar set of data as entered to updating user.
     *
     */
    public function saveValidatedUplodData($data)
    {
        try {
            DB::beginTransaction();
            $entitlementDataSet = json_decode($data['entitlementData']);
            $addedCount = 0;

            $validateResponse = $this->validateProcessedEntitltments($entitlementDataSet);
            if ($validateResponse['errorCount'] > 0) {
                $responseData = [
                    "errors" => true,
                    "validatedData" => $validateResponse['data']
                ];
                return $this->error(500, Lang::get('bulkUploadMessages.basic.ERR_UPLOAD_FILE'), $responseData);
            }


            foreach ($validateResponse['data'] as $key => $leaveEntitlement) {
                $leaveEntitlement = (array) $leaveEntitlement;
                $validationData = ModelValidator::validate($this->leaveEntitlementModel, $leaveEntitlement, false);
                if (!empty($validationData)) {
                    return $this->error(400, Lang::get('leaveEntitlementMessages.basic.ERR_CREATE'), $validationData);
                }

                $newLeaveEntitlement = $this->store->insert($this->leaveEntitlementModel, $leaveEntitlement, true);

                if ($newLeaveEntitlement) {
                    $addedCount++;
                }
            }

            $responseData = [
                "hasValidationErrors" => false,
                "errors" => [],
                "addedCount" => $addedCount
            ];

            DB::commit();
            return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_SAVE_UPLOAD_DATA'), $responseData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('bulkUploadMessages.basic.ERR_SAVE_UPLOAD_FILE'), null);
        }
    }


    /**
     * Following function validate upload data set of leave.
     *
     * @param $finaleData array containing leave data
     * @return int | String | array
     *
     * Sample output: ["data" => [], "errorCount" => 2]
     *
     */
    private function validateProcessedEntitltments($finaleData)
    {
        $finaleData = (array) $finaleData;
        // get company details
        $company = $this->queryBuilder::table('company')->first(['timeZone', 'leavePeriodStartingMonth', 'leavePeriodEndingMonth']);

        $errorRecordCount = 0;
        $validatedRecords = [];
        $errorCount = 0;

        //validate each record seperately
        foreach ($finaleData as $finalDatakey => $entitlementData) {
            $finaleData[$finalDatakey] = (array) $finaleData[$finalDatakey];
            $errorData = [
                "employeeNumber" => [],
                "entilementCount" => [],
                "usedCount" => [],
                "other" => []
            ];

            $entitlementData = (array) $entitlementData;
            $currentYear = Carbon::now()->format('Y');
            $currentDate = Carbon::now();
            $leavePeriodFrom = null;
            $employeeData = [];
            $leavePeriodTo = null;

            //get leave type id by name
            $leaveType = $this->queryBuilder::table('leaveType')->select('*')
                ->where('name', '=', $entitlementData['leaveType'])
                ->where('isDelete', false)
                ->first();

            //get employee Data
            $employeeData = $this->queryBuilder::table('employee')->select('*')
                ->where('employeeNumber', '=', $entitlementData['employeeNumber'])
                ->where('isDelete', false)
                ->where('isActive', true)
                ->first();

            $finaleData[$finalDatakey]['leaveTypeId'] = (!empty($leaveType->id)) ? $leaveType->id : null;
            $empLeaveTypeCombinationString = $finaleData[$finalDatakey]['employeeNumber'] . '-' . $leaveType->id;

            if (in_array($empLeaveTypeCombinationString, $validatedRecords)) {
                $errorData['other'][] = 'This Record is Duplicate';
                $errorCount++;
            } else {
                array_push($validatedRecords, $empLeaveTypeCombinationString);
            }

            if ($leaveType->leavePeriod === 'STANDARD') {
                $leavePeriod = $this->standardLeavePeriod($currentDate, $company);
            }

            if (!empty($employeeData) && !empty($leaveType)) {

                if ($leaveType->leavePeriod === 'HIRE_DATE_BASED') {
                    $hireDateObject = Carbon::createFromFormat('Y-m-d', $employeeData->hireDate);
                    $leavePeriod = $this->hireDateLeavePeriod($currentDate, $hireDateObject);
                }
            }

            if (!empty($leavePeriod)) {
                $finaleData[$finalDatakey]['leavePeriodFrom'] = $leavePeriod['from'];
                $finaleData[$finalDatakey]['leavePeriodTo'] = $leavePeriod['to'];
                $finaleData[$finalDatakey]['validFrom'] = $leavePeriod['from'];
                $finaleData[$finalDatakey]['validTo'] = $leavePeriod['to'];
            }


            //check already leave entitlement exist for this perticular employee for current year
            if (!empty($finaleData[$finalDatakey]['leavePeriodFrom']) && !empty($finaleData[$finalDatakey]['leavePeriodTo']) && !empty($employeeData) && !empty($leaveType)) {
                $leaveEntitlement = $this->queryBuilder::table('leaveEntitlement')
                    ->where('employeeId', $employeeData->id)
                    ->where('leaveTypeId', $leaveType->id)
                    ->where('leavePeriodFrom', $finaleData[$finalDatakey]['leavePeriodFrom'])
                    ->where('leavePeriodTo', $finaleData[$finalDatakey]['leavePeriodTo'])
                    ->first();

                if (!is_null($leaveEntitlement)) {
                    $errorData['other'][] = 'Already have entitilement for this leave type for this perticular employee for requested leave period';
                    $errorCount++;
                }
            }

            //validate employee number
            if (empty($entitlementData['employeeNumber'])) {
                $errorData['employeeNumber'][] = 'This is a mandatory field.';
            } else {
                if (empty($employeeData)) {
                    $errorData['employeeNumber'][] = 'Invalid employee number';
                    $errorCount++;
                } else {
                    $finaleData[$finalDatakey]['employeeId'] = $employeeData->id;
                }
            }


            //validate entitlement allocated count
            if (is_null($entitlementData['entilementCount'])) {
                $errorData['entilementCount'][] = 'This is a mandatory field.';
                $errorCount++;
            } else {
                if (!is_numeric($entitlementData['entilementCount'])) {
                    $errorData['entilementCount'][] = 'Only allowed numeric values';
                    $errorCount++;
                } else {
                    if ((float)$entitlementData['entilementCount'] < 0) {
                        $errorData['entilementCount'][] = 'Not allowed minus values';
                        $errorCount++;
                    } else {
                        if (!is_null($entitlementData['usedCount']) && is_numeric($entitlementData['usedCount'])) {
                            if ((float)$entitlementData['entilementCount'] < (float)$entitlementData['usedCount']) {
                                $errorData['entilementCount'][] = 'Should be greater than used count';
                                $errorCount++;
                            }
                        }
                    }
                }
            }

            //validate entitlement used count
            if (is_null($entitlementData['usedCount'])) {
                $errorData['usedCount'][] = 'This is a mandatory field.';
                $errorCount++;
            } else {
                if (!is_numeric($entitlementData['usedCount'])) {
                    $errorData['usedCount'][] = 'Only allowed numeric values';
                    $errorCount++;
                } else {
                    if ((float)$entitlementData['usedCount'] < 0) {
                        $errorData['usedCount'][] = 'Not allowed minus values';
                        $errorCount++;
                    } else {
                        if (!is_null($entitlementData['entilementCount']) && is_numeric($entitlementData['entilementCount'])) {
                            if ((float)$entitlementData['entilementCount'] < (float)$entitlementData['usedCount']) {
                                $errorData['usedCount'][] = 'Should be less than entitlement count';
                                $errorCount++;
                            }
                        }
                    }
                }
            }

            if (sizeof($errorData['usedCount']) == 0 && sizeof($errorData['entilementCount']) == 0 && sizeof($errorData['employeeNumber']) == 0 && sizeof($errorData['other']) == 0) {
                $finaleData[$finalDatakey]['hasErrors'] = false;
            } else {
                $finaleData[$finalDatakey]['hasErrors'] = true;
                $finaleData[$finalDatakey]['isfrontEndFix'] = false;
                $errorRecordCount++;
            }

            $finaleData[$finalDatakey]['errorData'] = $errorData;
        }

        $validationResponse = [
            'data' => $finaleData,
            'errorCount' => $errorCount
        ];

        return $validationResponse;
    }


    /**
     * Get standard leave period
     */
    private function standardLeavePeriod($dateObject, $company)
    {
        $leavePeriod = [
            'from' => null,
            'to' => null
        ];

        $leavePeriodStartingMonth = isset($company->leavePeriodStartingMonth) ? $company->leavePeriodStartingMonth : 1;
        $leavePeriodEndingMonth = isset($company->leavePeriodEndingMonth) ? $company->leavePeriodEndingMonth : 12;

        $currentYear = $dateObject->isoFormat('YYYY');
        $currentMonth = $dateObject->isoFormat('M');

        if ($leavePeriodStartingMonth < $leavePeriodEndingMonth) {
            $fromDateObj = Carbon::now();
            $fromDateObj->year = $currentYear;
            $fromDateObj->month = $leavePeriodStartingMonth;
            $fromDateObj->startOfMonth();

            $toDateObj = Carbon::now();
            $toDateObj->year = $currentYear;
            $toDateObj->month = $leavePeriodEndingMonth;
            $toDateObj->endOfMonth();
        } else {
            if ($leavePeriodStartingMonth > $currentMonth) { // for next year days
                $fromDateObj = Carbon::now();
                $fromDateObj->year = $currentYear;
                $fromDateObj->month = $leavePeriodStartingMonth;
                $fromDateObj->subYear()->startOfMonth();

                $toDateObj = Carbon::now();
                $toDateObj->year = $currentYear;
                $toDateObj->month = $leavePeriodEndingMonth;
                $toDateObj->endOfMonth();
            } else {
                $fromDateObj = Carbon::now();
                $fromDateObj->year = $currentYear;
                $fromDateObj->month = $leavePeriodStartingMonth;
                $fromDateObj->startOfMonth();

                $toDateObj = Carbon::now();
                $toDateObj->year = $currentYear;
                $toDateObj->month = $leavePeriodEndingMonth;
                $toDateObj->addYear()->endOfMonth();
            }
        }

        $leavePeriod['from'] = $fromDateObj->format('Y-m-d');
        $leavePeriod['to'] = $toDateObj->format('Y-m-d');

        return $leavePeriod;
    }



    /**
     * Get hire date base leave period
     */
    private function hireDateLeavePeriod($dateObject, $hireDateObject)
    {
        $leavePeriod = [
            'from' => null,
            'to' => null
        ];

        $hiredMonth = $hireDateObject->isoFormat('MM');
        $hiredDay = $hireDateObject->isoFormat('DD');

        $currentYearHireDate = Carbon::now();
        $currentYearHireDate->month = $hiredMonth;
        $currentYearHireDate->day = $hiredDay;

        if ($currentYearHireDate->greaterThan($dateObject)) { // if today is within previous leave period
            $leavePeriod['from'] = $currentYearHireDate->copy()->subYear()->format('Y-m-d');
            $leavePeriod['to'] = $currentYearHireDate->copy()->subDay()->format('Y-m-d');
        } else {
            $leavePeriod['from'] = $currentYearHireDate->format('Y-m-d');
            $leavePeriod['to'] = $currentYearHireDate->copy()->addYear()->subDay()->format('Y-m-d');
        }

        return $leavePeriod;
    }


    /**
     * Following function updates a company.
     *
     * @param $id user company id
     * @param $company array containing company data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Company updated Successfully",
     *      $data => {"title": "LK HR", ...} // has a similar set of data as entered to updating user.
     *
     */
    public function uploadTemplate($data)
    {
        try {
            $dynamicModel = $this->getModel($data['modelName'], true);
            $excelManipulator = new ExcelProcessor($dynamicModel, $this->session, $this->store);
            $destrcuturedFileName = explode('.', $data['fileName']);

            // code logic to decord the base64 file
            $fileData = base64_decode($data['file']);

            if ($data['fileType'] === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString() . '.xlsx';
            }

            if ($data['fileType'] === 'application/vnd.ms-excel') {
                $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString() . '.xls';
            }
            file_put_contents($tmpFilePath, $fileData);

            Excel::import($excelManipulator, $tmpFilePath);
            $validationResponse = $excelManipulator->getValidationFeildErrors();
            $getCount = $excelManipulator->getAddedCount();
            $getAddedEmployees = $excelManipulator->getAddedEmployees();

            if (!empty($validationResponse)) {
                $responseData = [
                    "hasValidationErrors" => true,
                    "errors" => $validationResponse,
                    "addedCount" => 0
                ];
                return $this->error(400, Lang::get('bulkUploadMessages.basic.VAL_ERR_UPLOAD_FILE'), $responseData);
            }

            // creating and storing the file object and the added employees
            $base64Uri = $data['fileType'] . ',' . base64_encode($fileData);
            $fileObject = [
                'fileName' => $destrcuturedFileName[0],
                'fileSize' => $data['fileSize'],
                'data' => $base64Uri
            ];
            $this->saveFileObjectAndUploadedEmployees($fileObject, $getAddedEmployees);
            $responseData = [
                "hasValidationErrors" => false,
                "errors" => [],
                "addedCount" => $getCount + 1
            ];
            return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_UPLOAD_FILE'), $responseData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('bulkUploadMessages.basic.ERR_UPLOAD_FILE'), null);
        }
    }

    /**
     * Following function inserts a fileObject to the File Store and adds a bulk upload history record.
     */
    private function saveFileObjectAndUploadedEmployees($fileObject, $uploadedEmployees)
    {
        try {

            $file = $this->fileStore->putBase64EncodedObject(
                $fileObject['fileName'],
                $fileObject['fileSize'],
                $fileObject['data']
            );

            if (!empty($file) && !empty($uploadedEmployees)) {

                $bulkUploadHistoryId = $this->store->getFacade()::table('bulkUploadHistory')
                    ->insertGetId(['fileObjectId' => $file->id, 'createdBy' => $this->session->getUser()->id]);

                $employeeLogData = [];
                foreach ($uploadedEmployees as $uploadedEmployee) {
                    $employeeLogData[] = [
                        'fileHistoryId' => $bulkUploadHistoryId,
                        'employeeId' => $uploadedEmployee
                    ];
                }
                $employeeLogData = collect($employeeLogData);
                $dataChunks = $employeeLogData->chunk(self::CHUNK_THRESHOLD);

                foreach ($dataChunks as $dataChunk) {
                    $this->store->getFacade()::table('bulkUploadedEmployees')
                        ->insert($dataChunk->toArray());
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * Following function retrives all the bulk uploaded history.
     */
    public function getBulkUploadedHistory()
    {
        try {
            // get company timezone
            $company = $this->store->getFacade()::table('company')->first('timeZone');
            $companyTimeZone =  $company->timeZone;

            $bulkHistoryRecords = $this->store->getFacade()::table('bulkUploadHistory')
                ->join('fileStoreObject', 'bulkUploadHistory.fileObjectId', '=', 'fileStoreObject.id')
                ->select('fileStoreObject.*')
                ->orderBy('bulkUploadHistory.createdAt', 'desc')
                ->get();

            $records = $bulkHistoryRecords->map(function ($item) use ($companyTimeZone) {
                $item->updatedAt = Carbon::parse($item->updatedAt)->copy()->tz($companyTimeZone);
                return $item;
            });

            if (!empty($records) && !is_null($records)) {
                return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_BULK_UPLOAD_HIST_LIST'), $records);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('bulkUploadMessages.basic.ERR_BULK_UPLOAD_HIST_LIST'), null);
        }
    }

    /**
     * Following function retrives file object for a given file id.
     */
    public function getFileObject($id)
    {
        try {
            if (is_null($id)) {
                return $this->error(400, Lang::get('bulkUploadMessages.basic.ERR_BULK_UPLOAD_HIST_FILE_OBJ_ID_NULL'), null);
            }

            $file = $this->fileStore->getBase64EncodedObject($id);
            return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_BULK_UPLOAD_HIST_FILE_OBJ'), $file);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('bulkUploadMessages.basic.VAL_ERR_UPLOAD_FILE'), null);
        }
    }

    /**
     * Following function download salary increment template
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Excel template downloaded successfully!",
     * ]
     */
    public function downloadSalaryIncrementTemplate()
    {
        try {
            $data = $this->genarateSupportDataSalaryIncrementExcelProcessor();
            $excelData = Excel::download(new SalaryIncrementExcelProcessor($data), 'salary-increment-template.xlsx');
            $file = $excelData->getFile()->getPathname();
            $fileData = file_get_contents($file);
            unlink($file);
            return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_GET_FILE'), base64_encode($fileData));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('bulkUploadMessages.basic.ERR_GET_FILE'), null);
        }
    }

    /**
     * Following function upload salary increment sheet
     *
     * @param $id user company id
     * @param $company array containing company data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Excel template upload successfully!",
     */
    public function uploadSalaryIncrementSheet($data)
    {
        try {
            $fileData = base64_decode($data['file']);

            if ($data['fileType'] === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString() . '.xlsx';
            }

            if ($data['fileType'] === 'application/vnd.ms-excel') {
                $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString() . '.xls';
            }

            if (empty($tmpFilePath)) return $this->error(400, Lang::get('bulkUploadMessages.basic.ERR_INVALID_FILE_TYPE'), null);

            file_put_contents($tmpFilePath, $fileData);

            $supportData = $this->genarateSupportDataSalaryIncrementExcelProcessor();
            $data = Excel::toArray(new SalaryIncrementExcelProcessor($supportData), $tmpFilePath);

            $employeeList = [];
            $payGradeList = [];
            foreach ($supportData as $payGrade) {
                $payGradeList[$payGrade['payGradeId']] = $payGrade['salaryComponents'];

                foreach ($payGrade['employees'] as $employee) {
                    $employeeList[$employee['employeeNumber']] = [
                        'employeeId' => $employee['employeeId'],
                        'payGradeId' => $payGrade['payGradeId'],
                        'employeeHireDate' => $employee['employeeHireDate'],
                        'currentSalaryRecordEffectiveDate' => $employee['currentSalaryRecordEffectiveDate']
                    ];
                }
            }

            $processData = [];
            foreach ($data as $sheet) {
                $headers = array_slice($sheet[0], 3);
                $sheet = array_slice($sheet, 1);

                foreach ($sheet as $row) {
                    $employeeNumber = $row[0];
                    $employeeName = $row[1];
                    $effectiveDate = $row[2];

                    $row = array_slice($row, 3);

                    $isNotEmptyRow = array_filter($row, function ($cellData) {
                        return trim($cellData) !== '' && floatval($cellData) !== 0;
                    });

                    if (empty($isNotEmptyRow)) continue;

                    $_data = [
                        'employeeNumber' => $employeeNumber,
                        'employeeName' => $employeeName,
                        'effectiveDate' => $effectiveDate,
                        'salaryDetails' => array_map(function ($header, $value) {
                            return [
                                'salaryComponentName' => $header,
                                'value' => floatval($value)
                            ];
                        }, $headers, $row)
                    ];

                    $employee = $employeeList[$employeeNumber];
                    if (is_null($employee)) {
                        $_data['salaryDetails'] = null;
                        $_data['validation'] = [
                            'error' => true,
                            'msg' => 'Employee not found'
                        ];
                        array_push($processData, $_data);
                        continue;
                    }

                    $payGrade = $payGradeList[$employee['payGradeId']];
                    if (empty($payGrade)) {
                        $_data['salaryDetails'] = null;
                        $_data['validation'] = [
                            'error' => true,
                            'msg' => 'Invalid pay grade'
                        ];
                        array_push($processData, $_data);
                        continue;
                    }

                    if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $effectiveDate)) {
                        $_data['salaryDetails'] = null;
                        $_data['validation'] = [
                            'error' => true,
                            'msg' => 'Invalid date'
                        ];
                        array_push($processData, $_data);
                        continue;
                    }

                    if ($effectiveDate < $employee['employeeHireDate']) {
                        $_data['salaryDetails'] = null;
                        $_data['validation'] = [
                            'error' => true,
                            'msg' => 'The effective date cannot be less than the hire data'
                        ];
                        array_push($processData, $_data);
                        continue;
                    }

                    if ($effectiveDate < $employee['currentSalaryRecordEffectiveDate']) {
                        $_data['salaryDetails'] = null;
                        $_data['validation'] = [
                            'error' => true,
                            'msg' => 'The effective date cannot be less than the previous salary record date'
                        ];
                        array_push($processData, $_data);
                        continue;
                    }

                    $invalidSalaryComponents = [];
                    $salaryDetails = [];
                    foreach ($headers as $index => $header) {
                        $value = $row[$index] ?? null;
                        if (is_null($value)) continue;

                        $salaryDetail = [
                            'salaryComponentName' => $header,
                            'value' => $value,
                            'validation' =>  is_numeric($value) && $value >= 0 ?  ['error' => false] : [
                                'error' => true,
                                'message' => 'Invalid Value'
                            ]
                        ];

                        $salaryComponentIndex = array_search($header, array_column($payGrade, 'name'));
                        if (!($salaryComponentIndex > -1)) {
                            $invalidSalaryComponents[] = $header;
                            array_push($salaryDetails, $salaryDetail);
                            continue;
                        }

                        $salaryDetail['salaryComponentId'] = $payGrade[$salaryComponentIndex]['salaryComponentId'];
                        array_push($salaryDetails, $salaryDetail);
                    }

                    $_data['employeeId'] = $employee['employeeId'];
                    $_data['salaryDetails'] = $salaryDetails;
                    $_data['validation'] = !empty($invalidSalaryComponents) ?
                        [
                            'error' => true,
                            'msg' => 'Invalid salary component',
                            'data' => $invalidSalaryComponents
                        ] : [
                            'error' => false
                        ];
                    array_push($processData, $_data);
                }
            }

            return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_UPLOAD_FILE'), $processData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('bulkUploadMessages.basic.ERR_UPLOAD_FILE'), null);
        }
    }


    /**
     * Following function upload salary increment sheet
     *
     * @param $id user company id
     * @param $company array containing company data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Excel template upload successfully!",
     */
    public function completeSalaryIncrementSheet($data)
    {
        try {
            $response = [];
            $employeeSalaryIds = [];

            foreach ($data as $record) {
                $employeeSalary = [
                    // 'employeeId' => $record['employeeId'],
                    'effectiveDate' => $record['effectiveDate'],
                    'salaryDetails' => json_encode(array_map(function ($value) {
                        return [
                            'salaryComponentId' => $value['salaryComponentId'],
                            'value' => $value['value']
                        ];
                    }, $record['salaryDetails']))
                ];

                $newEmployeeSalary = $this->employeeService->createEmployeeMultiRecord(
                    $record['employeeId'],
                    'salaries',
                    $employeeSalary
                );

                $response[] = $newEmployeeSalary['data'];
                $employeeSalaryIds[] = $newEmployeeSalary['data']['id'];
            }

            if (!empty($response)) {
                $this->store->insert(
                    $this->salaryIncrementUploadHistoryModel,
                    ['employeeSalaryIds' => json_encode($employeeSalaryIds)],
                    true
                );
            }

            return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_SALARY_INCREMENT_BULK_UPLOAD'), $response);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('bulkUploadMessages.basic.ERR_UPLOAD_FILE'), null);
        }
    }

    private function genarateSupportDataSalaryIncrementExcelProcessor()
    {
        $payGrades = $this->store->getFacade()::table('payGrades')->get()->toArray();
        $salaryComponents = $this->store->getFacade()::table('salaryComponents')->get()->toArray();
        $employees = $this->store->getFacade()::table('employee')
            ->leftJoin('employeeJob', 'employee.currentJobsId', '=', 'employeeJob.id')
            ->leftJoin('employeeSalary', 'employee.currentSalariesId', '=', 'employeeSalary.id')
            ->where('employee.isActive', 1)
            ->whereNotNull('employeeJob.payGradeId')
            ->whereIn('employeeJob.payGradeId', array_map(function ($_payGrade) {
                return $_payGrade->id;
            }, $payGrades))
            ->get([
                "employee.id AS employeeId",
                "employee.employeeNumber AS employeeNumber",
                "employee.hireDate AS employeeHireDate",
                DB::raw("CONCAT_WS(' ', employee.firstName, employee.middleName, employee.lastName)
                     AS employeeName"),
                "employeeJob.payGradeId AS payGrade",
                "employeeSalary.effectiveDate AS currentSalaryRecordEffectiveDate"
            ])
            ->toArray();

        $processedData = [];
        foreach ($employees as $employee) {
            if (!isset($processedData[$employee->payGrade])) {
                $payGrade = $payGrades[array_search($employee->payGrade, array_column($payGrades, 'id'))] ?? null;
                $components = array_map(function ($componentId) use ($salaryComponents) {
                    return [
                        'salaryComponentId' => $componentId,
                        'name' => $salaryComponents[array_search($componentId, array_column($salaryComponents, 'id'))]->name ?? null
                    ];
                }, json_decode($payGrade->salaryComponentIds));

                $processedData[$employee->payGrade] = [
                    "payGradeId" => $employee->payGrade,
                    "name" => $payGrade->name,
                    "salaryComponents" => $components
                ];
            }

            $processedData[$employee->payGrade]['employees'][] = [
                "employeeId" => $employee->employeeId,
                "employeeNumber" => $employee->employeeNumber,
                "employeeName" => $employee->employeeName,
                "employeeHireDate" => $employee->employeeHireDate,
                "currentSalaryRecordEffectiveDate" => $employee->currentSalaryRecordEffectiveDate
            ];
        }

        Log::error('processedData > ' . json_encode($processedData));

        return $processedData;
    }

    /**
     * Following function retrives all salary increment upload history.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Retrieved Successuflly",
     *      $data => []
     * ]
     */
    public function getSalaryIncrementUploadHistory($permittedFields, $options)
    {
        try {
            $filteredGenders = $this->store->getAll(
                $this->salaryIncrementUploadHistoryModel,
                $permittedFields,
                $options,
                [],
                [['isRollback', '=', false]]
            );
            return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_ALL_SALARY_INCREMENT_UPLOAD_HISTORY_RETRIVE'), $filteredGenders);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('bulkUploadMessages.basic.ERR_ALL_SALARY_INCREMENT_UPLOAD_HISTORY_RETRIVE'), null);
        }
    }

    /**
     * Following function rollback a salary increment upload.
     *
     * @param $id salary increment upload id
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "salary increment upload rollbacked successfully.",
     *      $data => null
     */
    public function rollbackSalaryIncrementUpload($id)
    {
        try {
            $historyRecord = $this->store->getById($this->salaryIncrementUploadHistoryModel, $id);
            if (is_null($historyRecord)) {
                return $this->error(404, Lang::get('bulkUploadMessages.basic.ERR_NONEXISTENT_SALARY_INCREMENT_UPLOAD'), null);
            }

            $affectedIds = json_decode($historyRecord->employeeSalaryIds);
            $hasDependents = !$this->store->getFacade()::table('employee')
                ->whereIn('currentSalariesId', $affectedIds)
                ->get()
                ->isEmpty();

            if ($hasDependents) {
                return $this->error(500, Lang::get('bulkUploadMessages.basic.ERR_DEPENDENT_SALARY_INCREMENT_UPLOAD_ROLLBACK'), null);
            }

            $this->store->getFacade()::table('employeeSalary')
                ->whereIn('id', $affectedIds)
                ->delete();

            $this->store->getFacade()::table('salaryIncrementUploadHistory')
                ->where('id', $id)
                ->update(['isRollback' => true]);

            return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_ROLLBACK'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('bulkUploadMessages.basic.ERR_ROLLBACK'), null);
        }
    }

    /**
     * Following function download salary increment template
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Excel template downloaded successfully!",
     * ]
     */
    public function downloadEmployeePromotionTemplate()
    {
        try {
            $data = $this->generateSupportDataEmployeePromotionExcelProcessor();
            $excelData = Excel::download(new EmployeePromotionExcelProcessor($data), 'salary-increment-template.xlsx');
            $file = $excelData->getFile()->getPathname();
            $fileData = file_get_contents($file);
            unlink($file);
            return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_GET_FILE'), base64_encode($fileData));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('bulkUploadMessages.basic.ERR_GET_FILE'), null);
        }
    }

    /**
     * Following function upload salary increment sheet
     *
     * @param $id user company id
     * @param $company array containing company data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Excel template upload successfully!",
     */
    public function uploadEmployeePromotionSheet($data)
    {
        try {
            $fileData = base64_decode($data['file']);

            if ($data['fileType'] === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString() . '.xlsx';
            }

            if ($data['fileType'] === 'application/vnd.ms-excel') {
                $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString() . '.xls';
            }

            if (empty($tmpFilePath)) return $this->error(400, Lang::get('bulkUploadMessages.basic.ERR_INVALID_FILE_TYPE'), null);

            file_put_contents($tmpFilePath, $fileData);

            $supportData = $this->generateSupportDataEmployeePromotionExcelProcessor();
            $data = Excel::toArray(new EmployeePromotionExcelProcessor($supportData), $tmpFilePath);
            $sheet = $data[0];

            $dataRange = 12;
            // $headers = array_slice($sheet[0], 0, $dataRange);
            unset($sheet[0]);

            $data = array_values(array_map(
                function ($row) use ($supportData) {
                    $employeeId = $this->objectArrayfind($supportData['employees'], $row[0]);
                    $employeeError = null;
                    if (is_null($row[0])) {
                        $employeeError = 'Required';
                    } else if (is_null($employeeId)) {
                        $employeeError = 'Invalid';
                    }

                    $effectiveDate = $row[11];
                    $effectiveDateError = null;
                    if (is_null($row[11])) {
                        $effectiveDateError = 'Required';
                    } else if (is_null($effectiveDate)) {
                        $effectiveDateError = 'Invalid';
                    }

                    $orgStructureEntityId = $this->objectArrayfind($supportData['orgEntities'], $row[1]);
                    $jobCategoryId = $this->objectArrayfind($supportData['jobCategories'], $row[2]);
                    $jobTitleId = $this->objectArrayfind($supportData['jobTitles'], $row[3]);
                    $payGradeId = $this->objectArrayfind($supportData['payGrades'], $row[4]);
                    $calendarId = $this->objectArrayfind($supportData['workCalendars'], $row[5]);
                    $reportsToEmployeeId = $this->objectArrayfind($supportData['reportingPersons'], $row[6]);
                    $functionalReportsToEmployeeId = $this->objectArrayfind($supportData['reportingPersons'], $row[7]);
                    $locationId = $this->objectArrayfind($supportData['locations'], $row[8]);
                    $promotionTypeId = $this->objectArrayfind($supportData['promotionTypes'], $row[9]);
                    $promotionReason = $row[9];

                    return [
                        'employeeId' => $employeeId,
                        'effectiveDate' => $effectiveDate,
                        'orgStructureEntityId' => $orgStructureEntityId,
                        'jobCategoryId' => $jobCategoryId,
                        'jobTitleId' => $jobTitleId,
                        'payGradeId' => $payGradeId,
                        'calendarId' => $calendarId,
                        'reportsToEmployeeId' => $reportsToEmployeeId,
                        'functionalReportsToEmployeeId' => $functionalReportsToEmployeeId,
                        'locationId' => $locationId,
                        'promotionTypeId' => $promotionTypeId,
                        'promotionReason' => $promotionReason,
                        'error' => [
                            'employeeId' => $employeeError,
                            'effectiveDate' => $effectiveDateError,
                            'orgStructureEntityId' => !is_null($row[1]) && is_null($orgStructureEntityId) ? 'Invalid - ' . $row[1] : null,
                            'jobCategoryId' => !is_null($row[2]) && is_null($jobCategoryId) ? 'Invalid - ' . $row[2] : null,
                            'jobTitleId' => !is_null($row[3]) && is_null($jobTitleId) ? 'Invalid - ' . $row[3] : null,
                            'payGradeId' => !is_null($row[4]) && is_null($payGradeId) ? 'Invalid - ' . $row[4] : null,
                            'calendarId' => !is_null($row[5]) && is_null($calendarId) ? 'Invalid - ' . $row[5] : null,
                            'reportsToEmployeeId' => !is_null($row[6]) && is_null($reportsToEmployeeId) ? 'Invalid - ' . $row[6] : null,
                            'functionalReportsToEmployeeId' => !is_null($row[7]) && is_null($functionalReportsToEmployeeId) ? 'Invalid - ' . $row[7] : null,
                            'locationId' => !is_null($row[8]) && is_null($locationId) ? 'Invalid - ' . $row[8] : null,
                            'promotionTypeId' => !is_null($row[9]) && is_null($promotionTypeId) ? 'Invalid - ' . $row[9] : null
                        ]
                    ];
                },
                array_filter($sheet, function ($row) use ($dataRange) {
                    return !empty(array_filter(array_slice($row, 0, $dataRange), function ($value) {
                        return $value !== null;
                    }));
                })
            ));

            return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_UPLOAD_FILE'), $data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('bulkUploadMessages.basic.ERR_UPLOAD_FILE'), null);
        }
    }


    /**
     * Following function upload salary increment sheet
     *
     * @param $id user company id
     * @param $company array containing company data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Excel template upload successfully!",
     */
    public function completeEmployeePromotionSheet($data)
    {
        try {
            $response = [];
            $affectedIds = [];

            foreach ($data as $record) {
                unset($record['id']);
                $_response = $this->employeeJourneyService->createEmployeeJourneyEvent($record['employeeId'], 'PROMOTIONS', $record);

                $response[] = $_response['data'];
                Log::error(json_encode($_response['data']));
                $affectedIds[] = $_response['data']['id'];
            }

            if (!empty($response)) {
                $this->store->insert(
                    $this->employeeJourneyUploadHistoryModel,
                    ['employeeJourneyType' => 'PROMOTIONS', 'affectedIds' => json_encode($affectedIds)],
                    true
                );
            }

            return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_SALARY_INCREMENT_BULK_UPLOAD'), $response);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('bulkUploadMessages.basic.ERR_UPLOAD_FILE'), null);
        }
    }

    public function getEmployeePromotionSupportData()
    {
        $data = $this->generateSupportDataEmployeePromotionExcelProcessor();
        $data['employees'] = $this->store->getFacade()::table('employee')
            ->select('id', 'fullName AS name')
            ->get()
            ->toArray();

        return $this->success(200, Lang::get('bulkUploadMessages.basic.bulkUploadMessages.basic.SUCC_GET_FILE'), $data);
    }

    private function generateSupportDataEmployeePromotionExcelProcessor()
    {
        $employeeCount = $this->store->getFacade()::table('employee')
            ->where('isActive', 1)
            ->count();
        $employees = $this->store->getFacade()::table('employee')
            ->select('id', 'employeeNumber AS name')
            ->get()
            ->toArray();
        // $orgEntities = $this->store->getFacade()::table('orgEntity')
        //     ->where('isDelete', 0)
        //     ->select('id', 'name')
        //     ->get()
        //     ->toArray();
        $orgEntities = DB::table('orgEntity')
            ->select('id', 'name')
            ->from(DB::raw('(WITH RECURSIVE path_cte AS (
                    SELECT id, parentEntityId, name, CAST(name AS VARCHAR(2000)) AS path
                    FROM orgEntity
                    WHERE parentEntityId IS NULL

                    UNION ALL

                    SELECT t.id, t.parentEntityId, t.name, CONCAT(p.path, " > ", t.name)
                    FROM orgEntity t
                    INNER JOIN path_cte p ON t.parentEntityId = p.id
                )
                SELECT id, path AS name
                FROM path_cte) as temp_table'))
            ->get()
            ->toArray();
        Log::error(json_encode($orgEntities));
        $jobCategories = $this->store->getFacade()::table('jobCategory')
            ->where('isDelete', 0)
            ->select('id', 'name')
            ->get()
            ->toArray();
        $jobTitles = $this->store->getFacade()::table('jobTitle')
            ->where('isDelete', 0)
            ->select('id', 'name')
            ->get()
            ->toArray();
        $payGrades = $this->store->getFacade()::table('payGrades')
            ->where('isDelete', 0)
            ->select('id', 'name')
            ->get()
            ->toArray();
        $workCalendars = $this->store->getFacade()::table('workCalendar')
            ->select('id', 'name')
            ->get()
            ->toArray();
        $reportingPersons = $this->store->getFacade()::table('employee')
            ->join('user', 'user.employeeId', '=', 'employee.id')
            ->where('employee.isDelete', 0)
            ->whereNotNull('user.managerRoleId')
            ->select('employee.id', DB::raw("CONCAT_WS(' ', employee.firstName, employee.middleName, employee.lastName) AS name"))
            ->get()
            ->toArray();
        $locations = $this->store->getFacade()::table('location')
            ->where('isDelete', 0)
            ->select('id', 'name')
            ->get()
            ->toArray();
        $promotionTypes = $this->store->getFacade()::table('promotionType')
            ->where('isDelete', 0)
            ->select('id', 'name')
            ->get()
            ->toArray();

        return [
            'employeeCount' => $employeeCount,
            'employees' => $employees,
            'orgEntities' => $orgEntities,
            'jobCategories' => $jobCategories,
            'jobTitles' => $jobTitles,
            'payGrades' => $payGrades,
            'workCalendars' => $workCalendars,
            'reportingPersons' => $reportingPersons,
            'locations' => $locations,
            'promotionTypes' => $promotionTypes
        ];
    }

    /**
     * Following function retrives all salary increment upload history.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Retrieved Successuflly",
     *      $data => []
     * ]
     */
    public function getEmployeePromotionUploadHistory($permittedFields, $options)
    {
        try {
            $filteredGenders = $this->store->getAll(
                $this->employeeJourneyUploadHistoryModel,
                $permittedFields,
                $options,
                [],
                [['isRollback', '=', false], ['employeeJourneyType', '=', 'PROMOTIONS']]
            );
            return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_ALL_SALARY_INCREMENT_UPLOAD_HISTORY_RETRIVE'), $filteredGenders);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('bulkUploadMessages.basic.ERR_ALL_SALARY_INCREMENT_UPLOAD_HISTORY_RETRIVE'), null);
        }
    }

    /**
     * Following function rollback a salary increment upload.
     *
     * @param $id salary increment upload id
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "salary increment upload rollbacked successfully.",
     *      $data => null
     */
    public function rollbackEmployeePromotionUpload($id)
    {
        try {
            $historyRecord = $this->store->getById($this->employeeJourneyUploadHistoryModel, $id);
            if (is_null($historyRecord)) {
                return $this->error(404, Lang::get('bulkUploadMessages.basic.ERR_NONEXISTENT_SALARY_INCREMENT_UPLOAD'), null);
            }

            $affectedIds = json_decode($historyRecord->affectedIds);
            $hasDependents = !$this->store->getFacade()::table('employee')
                ->whereIn('currentJobsId', $affectedIds)
                ->get()
                ->isEmpty();

            if ($hasDependents) {
                return $this->error(500, Lang::get('bulkUploadMessages.basic.ERR_DEPENDENT_SALARY_INCREMENT_UPLOAD_ROLLBACK'), null);
            }

            $this->store->getFacade()::table('employeeJob')
                ->whereIn('id', $affectedIds)
                ->delete();

            $this->store->getFacade()::table('employeeJourneyUploadHistory')
                ->where('id', $id)
                ->update(['isRollback' => true]);

            return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_ROLLBACK'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('bulkUploadMessages.basic.ERR_ROLLBACK'), null);
        }
    }

    /**
     * Following function download salary increment template
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Excel template downloaded successfully!",
     * ]
     */
    public function downloadEmployeeTransferTemplate()
    {
        try {
            $data = $this->generateSupportDataEmployeeTransferExcelProcessor();
            $excelData = Excel::download(new EmployeeTransferExcelProcessor($data), 'salary-increment-template.xlsx');
            $file = $excelData->getFile()->getPathname();
            $fileData = file_get_contents($file);
            unlink($file);
            return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_GET_FILE'), base64_encode($fileData));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('bulkUploadMessages.basic.ERR_GET_FILE'), null);
        }
    }

    /**
     * Following function upload salary increment sheet
     *
     * @param $id user company id
     * @param $company array containing company data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Excel template upload successfully!",
     */
    public function uploadEmployeeTransferSheet($data)
    {
        try {
            $fileData = base64_decode($data['file']);

            if ($data['fileType'] === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString() . '.xlsx';
            }

            if ($data['fileType'] === 'application/vnd.ms-excel') {
                $tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString() . '.xls';
            }

            if (empty($tmpFilePath)) return $this->error(400, Lang::get('bulkUploadMessages.basic.ERR_INVALID_FILE_TYPE'), null);

            file_put_contents($tmpFilePath, $fileData);

            $supportData = $this->generateSupportDataEmployeeTransferExcelProcessor();
            $data = Excel::toArray(new EmployeeTransferExcelProcessor($supportData), $tmpFilePath);
            $sheet = $data[0];

            $dataRange = 9;
            // $headers = array_slice($sheet[0], 0, $dataRange);
            unset($sheet[0]);

            $data = array_values(array_map(
                function ($row) use ($supportData) {
                    $employeeId = $this->objectArrayfind($supportData['employees'], $row[0]);
                    $employeeError = null;
                    if (is_null($row[0])) {
                        $employeeError = 'Required';
                    } else if (is_null($employeeId)) {
                        $employeeError = 'Invalid';
                    }

                    $effectiveDate = trim($row[8]);
                    $effectiveDateError = null;
                    if (is_null($row[8])) {
                        $effectiveDateError = 'Required';
                    } else if (is_null($effectiveDate)) {
                        $effectiveDateError = 'Invalid';
                    }

                    $orgStructureEntityId = $this->objectArrayfind($supportData['orgEntities'], $row[1]);
                    $locationId = $this->objectArrayfind($supportData['locations'], $row[2]);
                    $jobTitleId = $this->objectArrayfind($supportData['jobTitles'], $row[3]);
                    $payGradeId = $this->objectArrayfind($supportData['payGrades'], $row[4]);
                    $calendarId = $this->objectArrayfind($supportData['workCalendars'], $row[5]);
                    $transferTypeId = $this->objectArrayfind($supportData['transferTypes'], $row[6]);
                    $transferReason = $row[7];

                    return [
                        'employeeId' => $employeeId,
                        'effectiveDate' => $effectiveDate,
                        'orgStructureEntityId' => $orgStructureEntityId,
                        'jobTitleId' => $jobTitleId,
                        'payGradeId' => $payGradeId,
                        'calendarId' => $calendarId,
                        'locationId' => $locationId,
                        'transferTypeId' => $transferTypeId,
                        'transferReason' => $transferReason,
                        'error' => [
                            'employeeId' => $employeeError,
                            'effectiveDate' => $effectiveDateError,
                            'orgStructureEntityId' => !is_null($row[1]) && is_null($orgStructureEntityId) ? 'Invalid - ' . $row[1] : null,
                            'locationId' => !is_null($row[2]) && is_null($locationId) ? 'Invalid - ' . $row[2] : null,
                            'jobTitleId' => !is_null($row[3]) && is_null($jobTitleId) ? 'Invalid - ' . $row[3] : null,
                            'payGradeId' => !is_null($row[4]) && is_null($payGradeId) ? 'Invalid - ' . $row[4] : null,
                            'calendarId' => !is_null($row[5]) && is_null($calendarId) ? 'Invalid - ' . $row[5] : null,
                            'transferTypeId' => !is_null($row[6]) && is_null($transferTypeId) ? 'Invalid - ' . $row[6] : null
                        ]
                    ];
                },
                array_filter($sheet, function ($row) use ($dataRange) {
                    return !empty(array_filter(array_slice($row, 0, $dataRange), function ($value) {
                        return $value !== null;
                    }));
                })
            ));

            return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_UPLOAD_FILE'), $data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('bulkUploadMessages.basic.ERR_UPLOAD_FILE'), null);
        }
    }


    /**
     * Following function upload salary increment sheet
     *
     * @param $id user company id
     * @param $company array containing company data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Excel template upload successfully!",
     */
    public function completeEmployeeTransferSheet($data)
    {
        try {
            $response = [];
            $affectedIds = [];

            foreach ($data as $record) {
                unset($record['id']);
                $_response = $this->employeeJourneyService->createEmployeeJourneyEvent($record['employeeId'], 'TRANSFERS', $record);

                $response[] = $_response['data'];
                $affectedIds[] = $_response['data']['id'];
            }

            if (!empty($response)) {
                $this->store->insert(
                    $this->employeeJourneyUploadHistoryModel,
                    ['employeeJourneyType' => 'PROMOTIONS', 'affectedIds' => json_encode($affectedIds)],
                    true
                );
            }

            return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_SALARY_INCREMENT_BULK_UPLOAD'), $response);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('bulkUploadMessages.basic.ERR_UPLOAD_FILE'), null);
        }
    }

    public function getEmployeeTransferSupportData()
    {
        $data = $this->generateSupportDataEmployeeTransferExcelProcessor();
        $data['employees'] = $this->store->getFacade()::table('employee')
            ->select('id', 'fullName AS name')
            ->get()
            ->toArray();

        return $this->success(200, Lang::get('bulkUploadMessages.basic.bulkUploadMessages.basic.SUCC_GET_FILE'), $data);
    }

    private function generateSupportDataEmployeeTransferExcelProcessor()
    {
        $employeeCount = $this->store->getFacade()::table('employee')
            ->where('isActive', 1)
            ->count();
        $employees = $this->store->getFacade()::table('employee')
            ->select('id', 'employeeNumber AS name')
            ->get()
            ->toArray();
        // $orgEntities = $this->store->getFacade()::table('orgEntity')
        //     ->where('isDelete', 0)
        //     ->select('id', 'name')
        //     ->get()
        //     ->toArray();
        $orgEntities = DB::table('orgEntity')
            ->select('id', 'name')
            ->from(DB::raw('(WITH RECURSIVE path_cte AS (
                    SELECT id, parentEntityId, name, CAST(name AS VARCHAR(2000)) AS path
                    FROM orgEntity
                    WHERE parentEntityId IS NULL

                    UNION ALL

                    SELECT t.id, t.parentEntityId, t.name, CONCAT(p.path, " > ", t.name)
                    FROM orgEntity t
                    INNER JOIN path_cte p ON t.parentEntityId = p.id
                )
                SELECT id, path AS name
                FROM path_cte) as temp_table'))
            ->get()
            ->toArray();
        $jobCategories = $this->store->getFacade()::table('jobCategory')
            ->where('isDelete', 0)
            ->select('id', 'name')
            ->get()
            ->toArray();
        $jobTitles = $this->store->getFacade()::table('jobTitle')
            ->where('isDelete', 0)
            ->select('id', 'name')
            ->get()
            ->toArray();
        $payGrades = $this->store->getFacade()::table('payGrades')
            ->where('isDelete', 0)
            ->select('id', 'name')
            ->get()
            ->toArray();
        $workCalendars = $this->store->getFacade()::table('workCalendar')
            ->select('id', 'name')
            ->get()
            ->toArray();
        $reportingPersons = $this->store->getFacade()::table('employee')
            ->join('user', 'user.employeeId', '=', 'employee.id')
            ->where('employee.isDelete', 0)
            ->whereNotNull('user.managerRoleId')
            ->select('employee.id', DB::raw("CONCAT_WS(' ', employee.firstName, employee.middleName, employee.lastName) AS name"))
            ->get()
            ->toArray();
        $locations = $this->store->getFacade()::table('location')
            ->where('isDelete', 0)
            ->select('id', 'name')
            ->get()
            ->toArray();
        $transferTypes = $this->store->getFacade()::table('transferType')
            ->where('isDelete', 0)
            ->select('id', 'name')
            ->get()
            ->toArray();

        return [
            'employeeCount' => $employeeCount,
            'employees' => $employees,
            'orgEntities' => $orgEntities,
            'jobCategories' => $jobCategories,
            'jobTitles' => $jobTitles,
            'payGrades' => $payGrades,
            'workCalendars' => $workCalendars,
            'reportingPersons' => $reportingPersons,
            'locations' => $locations,
            'transferTypes' => $transferTypes
        ];
    }

    /**
     * Following function retrives all salary increment upload history.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Retrieved Successuflly",
     *      $data => []
     * ]
     */
    public function getEmployeeTransferUploadHistory($permittedFields, $options)
    {
        try {
            $filteredGenders = $this->store->getAll(
                $this->employeeJourneyUploadHistoryModel,
                $permittedFields,
                $options,
                [],
                [['isRollback', '=', false], ['employeeJourneyType', '=', 'TRANSFERS']]
            );
            return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_ALL_SALARY_INCREMENT_UPLOAD_HISTORY_RETRIVE'), $filteredGenders);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('bulkUploadMessages.basic.ERR_ALL_SALARY_INCREMENT_UPLOAD_HISTORY_RETRIVE'), null);
        }
    }

    /**
     * Following function rollback a salary increment upload.
     *
     * @param $id salary increment upload id
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "salary increment upload rollbacked successfully.",
     *      $data => null
     */
    public function rollbackEmployeeTransferUpload($id)
    {
        try {
            $historyRecord = $this->store->getById($this->employeeJourneyUploadHistoryModel, $id);
            if (is_null($historyRecord)) {
                return $this->error(404, Lang::get('bulkUploadMessages.basic.ERR_NONEXISTENT_SALARY_INCREMENT_UPLOAD'), null);
            }

            $affectedIds = json_decode($historyRecord->affectedIds);
            $hasDependents = !$this->store->getFacade()::table('employee')
                ->whereIn('currentJobsId', $affectedIds)
                ->get()
                ->isEmpty();

            if ($hasDependents) {
                return $this->error(500, Lang::get('bulkUploadMessages.basic.ERR_DEPENDENT_SALARY_INCREMENT_UPLOAD_ROLLBACK'), null);
            }

            $this->store->getFacade()::table('employeeJob')
                ->whereIn('id', $affectedIds)
                ->delete();

            $this->store->getFacade()::table('employeeJourneyUploadHistory')
                ->where('id', $id)
                ->update(['isRollback' => true]);

            return $this->success(200, Lang::get('bulkUploadMessages.basic.SUCC_ROLLBACK'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('bulkUploadMessages.basic.ERR_ROLLBACK'), null);
        }
    }

    private function objectArrayfind($options, $value, $enumKey = "id", $enumValue = "name")
    {
        $object = null;
        foreach ($options as $option) {
            if ($option->$enumValue == $value) {
                $object = $option;
                break;
            }
        }

        return !empty($object) ? $object->$enumKey : null;
    }
}
