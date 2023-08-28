<?php

namespace App\Services;

use App\Library\Interfaces\ModelReaderInterface;
use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\FileStore;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Library\Session;
use App\Library\Util;
use App\Traits\JsonModelReader;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Traits\ConfigHelper;
use App\Traits\Crypter;
use DateTime;
use DateInterval;
use DatePeriod;
use date;

/**
 * Name: PayRollService
 * Purpose: Performs tasks related to the pay roll.
 * Description: PayRollService class is called by the payRollController where the requests related
 * to pay roll (basic operations and others)
 * Module Creator: Tharindu Darshana
 */
class PayRollService extends BaseService
{
    use JsonModelReader;
    use Crypter;
    use ConfigHelper;

    private $store;
    private $employeeModel;
    private $fileStore;
    private $session;
    private $orgEntityModel;

    public function __construct(Store $store, FileStore $fileStore, Session $session)
    {
        $this->store = $store;
        $this->fileStore = $fileStore;
        $this->session = $session;
        $this->employeeModel = $this->getModel('employee', true);
        $this->employeeSalaryModel = $this->getModel('employeeSalary', true);
        $this->orgEntityModel =  $this->getModel('orgEntity', true);
    }


    /**
     * Following function return employee profile details for pay roll
     *
     * @param $options
     * @return array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "race deleted successfully.",
     *      $data => [$employeeProfiles]
     *
     */
    public function getEmployeeProfilesForPayRoll($options)
    {
        try {

            $offset = 100;
            $encryptMethod = config('app.crypter_key');
            $fields = array_values($this->employeeModel->toArray()["fields"]);
            $companyName = '-';

            $companyDetails = DB::table('company')->where('id', '=', 1)->first();
            $companyDetails = (array) $companyDetails;

            if (!empty($companyDetails)) {
                $companyName = $companyDetails['name'];
            }

            $employees = DB::table('employee')
                ->leftJoin('gender', 'gender.id', '=', 'employee.genderId')
                // ->leftJoin('employeeSalary','employeeSalary.id','=','employee.currentSalariesId')
                // ->leftJoin('employeeBankAccount','employeeBankAccount.id','=','employee.currentBankAccountsId')
                ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                ->leftJoin('jobTitle', 'jobTitle.id', '=', 'employeeJob.jobTitleId')
                ->leftJoin('scheme', 'scheme.id', '=', 'employeeJob.schemeId')
                // ->leftJoin('department', 'department.id', '=', 'employeeJob.departmentId')
                // ->leftJoin('division', 'division.id', '=', 'employeeJob.divisionId')
                ->leftJoin('employmentStatus', 'employmentStatus.id', '=', 'employeeJob.employmentStatusId')
                ->selectRaw("CONCAT_WS(' ', firstName, middleName, lastName) AS employeeFullName, employee.title, employee.initials")
                ->selectRaw('employee.id AS employeeID, employee.currentSalariesId, employee.currentJobsId, employee.currentBankAccountsId,employee.firstName AS employeeFirstName , employee.lastName AS employeeLastName, employeeNumber, employee.isDelete, employee.isActive')
                ->selectRaw('jobTitle.name AS employeeDesignation, gender.name AS gender, employee.nicNumber')
                ->selectRaw('employee.hireDate AS dateOfAppoinment, employmentStatus.name AS employeeEmploymentType, employee.workEmail AS email, employee.dateOfBirth')
                ->selectRaw('employee.updatedAt, scheme.id as schemeId, scheme.name as scheme, employee.epfNumber, employee.etfNumber');

            $employees =  $employees->where('employee.isDelete', '=', false);


            if (!is_null($options['lastDataSyncTimeStamp'])) {
                $employees = $employees->where('employee.updatedAt', ">=", $options['lastDataSyncTimeStamp']);
            }
            $totalRecordCount = $employees->count();
            $totalNumOfPages = ceil($totalRecordCount / $offset);


            if (is_null($options['pageNo'])) {
                $currentPage = 1;
                $employees = $employees->skip(0)->take($offset)->get();
            } else {
                $skip = ($options['pageNo'] - 1) * $offset;
                $currentPage = (int) $options['pageNo'];
                $employees = $employees->skip($skip)->take($offset)->get();
            }

            // get basic salary related Field
            $basicSalaryField = $this->employeeSalaryModel->getSalaryComponentTypeWiseFields('basic');

            // get fixed allowance related Fields
            $fixedAllowanceFields = $this->employeeSalaryModel->getSalaryComponentTypeWiseFields('allowance');

            foreach ($employees as $key => $employee) {
                $employee = (array) $employee;
                $employees[$key]->dateOfResign = $this->deriveDateOfResign($employee['employeeID']);
                $employees[$key]->company = $companyName;
                $employees[$key]->salaryDetails = $this->getEmployeeRelatedSalaryDetails($employee['employeeID']);
                $employees[$key]->orgStructureEntityDetails = $this->getEmployeeOrgStructureDetails($employee['employeeID'],$employees[$key]->currentJobsId);

                unset($employees[$key]->currentSalariesId);
                unset($employees[$key]->currentJobsId);

                $bankAccountDetail = [];

                if (!empty($employee['currentBankAccountsId'])) {

                    //get related bankaccount
                    $employeeBankData = DB::table('employeeBankAccount')
                        ->leftJoin('bank','bank.id','=','employeeBankAccount.bankId')
                        ->leftJoin('bankBranch','bankBranch.id','=','employeeBankAccount.branchId')
                        ->selectRaw('bank.name as bankName,bank.bankCode,bankBranch.branchCode, bankBranch.name as branchName,employeeBankAccount.id,employeeBankAccount.bankId,employeeBankAccount.branchId,AES_DECRYPT(employeeBankAccount.accountNumber, "'.$encryptMethod.'") AS bankAccountNumber')
                        ->where('employeeBankAccount.id', '=', $employee['currentBankAccountsId'])->first();


                    if (!is_null($employeeBankData)) {
                        $employeeBankData = (array) $employeeBankData;
                        $temp = [];
                        $temp = [
                            'bankCode' => $employeeBankData['bankCode'],
                            'bankName' => $employeeBankData['bankName'],
                            'branchCode' => $employeeBankData['branchCode'],
                            'branchName' => $employeeBankData['branchName'],
                            'accountNo' => $employeeBankData['bankAccountNumber']
                        ];
                        $bankAccountDetail[] = $temp;
                    }
                }
                  
                // $employees[$key]->bankDetails = json_encode($bankAccountDetail);
                $employees[$key]->bankDetails = $bankAccountDetail;
                unset($employees[$key]->currentBankAccountsId);
            }

            $dataSet = [
                'employees' => $employees,
                'currentPage' => $currentPage,
                'totalPages' => $totalNumOfPages
            ];

            return $this->success(200, Lang::get('payRollMessages.basic.SUCC_EMP_PROFILE_RETRIVED'), $dataSet);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('payRollMessages.basic.ERR_EMP_PROFILE_RETRIVED'), null);
        }
    }

    public function getEmployeeAttendanceSummeryForPayRoll($options)
    {
        try {

            $offset = 100;

            $fromDate = $options['from'];
            $toDate = $options['to'];
            $endDate = Carbon::parse($toDate)->addDays(1);

            if (empty($fromDate) || empty($toDate)) {
                return $this->error('500', Lang::get('payRollMessages.basic.ERR_ATTENDANCE_SUMMARY_DATE_RANGE'), null);
            }

            $nonTerminateEmployeesCollection = DB::table('employee')
                ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                ->selectRaw("CONCAT_WS(' ', firstName, middleName, lastName) AS employeeFullName")
                ->selectRaw('employee.id AS employeeID, employeeNumber, employee.hireDate AS dateOfAppoinment')
                ->where('employee.isDelete', '=', false)
                ->where('employeeJob.employeeJourneyType', '!=', 'RESIGNATIONS')->get(['employee.id AS employeeID', 'employeeNumber', 'employee.hireDate AS dateOfAppoinment', 'employeeJob.employmentStatusId'])->toArray();

            $terminateWithinDatePeriodEmployeesCollection = DB::table('employee')
                ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                ->selectRaw("CONCAT_WS(' ', firstName, middleName, lastName) AS employeeFullName")
                ->selectRaw('employee.id AS employeeID, employeeNumber, employee.hireDate AS dateOfAppoinment')
                ->where('employee.isDelete', '=', false)
                ->where('employeeJob.employeeJourneyType', '=', 'RESIGNATIONS')
                ->whereBetween('employeeJob.effectiveDate', array($fromDate, $endDate))->get(['employee.id AS employeeID', 'employeeNumber', 'employee.hireDate AS dateOfAppoinment', 'employeeJob.employmentStatusId'])->toArray();

            $collection = collect($nonTerminateEmployeesCollection);

            $mergedEmployees = $collection->merge($terminateWithinDatePeriodEmployeesCollection);

            $totalRecordCount = $mergedEmployees->count();
            $totalNumOfPages = ceil($totalRecordCount / $offset);

            if (is_null($options['pageNo'])) {
                $currentPage = 1;
                $mergedEmployees = $mergedEmployees->skip(0)->take($offset)->all();
            } else {
                $skip = ($options['pageNo'] - 1) * $offset;
                $currentPage = (int)$options['pageNo'];
                $mergedEmployees = $mergedEmployees->skip($skip)->take($offset)->all();
            }

            $processedEmployeeAttendance = [];
            $employees = [];
            foreach ($mergedEmployees as $key => $employee) {
                $employee = (array)$employee;
                $attendanceData = $this->getAttendanceSummeryData($fromDate, $toDate, $employee);
                $otData = $this->getDateRangeWiseOtDataForEmployee($fromDate, $toDate, $employee);
                $attendanceData['otDetails'] = $otData;

                $employees[] = $attendanceData;
            }

            $dataSet = [
                'attendanceSummaryRecords' => $employees,
                'currentPage' => $currentPage,
                'totalPages' => $totalNumOfPages
            ];

            return $this->success(200, Lang::get('payRollMessages.basic.SUCC_EMP_ATTENDANCE_SUMMARY_RETRIVED'), $dataSet);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('payRollMessages.basic.ERR_EMP_ATTENDANCE_SUMMARY_RETRIVED'), null);
        }
    }


    public function getDateRangeWiseOtDataForEmployee($fromDate, $toDate, $employeeRecord)
    {
        $endDate = Carbon::parse($toDate)->addDays(1);
        $employeeRecord = (array) $employeeRecord;

        $otData = DB::table('attendanceSummaryPayTypeDetail')
            ->leftJoin('attendance_summary', 'attendance_summary.id', '=', 'attendanceSummaryPayTypeDetail.summaryId')
            ->leftJoin('payType', 'payType.id', '=', 'attendanceSummaryPayTypeDetail.payTypeId')
            ->where('attendance_summary.employeeId', '=', $employeeRecord['employeeID'])
            ->where('payType.type', '=', 'OVERTIME')
            ->whereBetween('date', array($fromDate, $toDate))
            ->groupBy('attendanceSummaryPayTypeDetail.payTypeId')
            ->selectRaw('payType.name as otTypeName, payType.code as otTypeCode,payType.rate,(sum(attendanceSummaryPayTypeDetail.approvedWorkTime))/60 as totalOtHours, attendanceSummaryPayTypeDetail.payTypeId as otTypeId')->get()->toArray();

        return $otData;
    }
    public function getAttendanceSummeryData($fromDate, $toDate, $employeeRecord)
    {
        $endDate = Carbon::parse($toDate)->addDays(1);
        $employeeRecord = (array) $employeeRecord;
        $attendanceData = DB::table('attendance_summary')
            ->where('attendance_summary.employeeId', '=', $employeeRecord['employeeID'])
            ->whereBetween('date', array($fromDate, $toDate))->get()->toArray();

        $noOfDaysNopay = 0;
        $noOfDaysExpected = 0;
        $noOfDaysPresent = 0;
        $noOfDaysAbsent = 0;
        $noOfDaysPayCut = 0;
        $lateHrs = 0;
        $totalExpectedWorkTime = 0;
        $totalActualWorkTime = 0;

        foreach ($attendanceData as $key => $attendance) {
            $attendance = (array) $attendance;
            if ($attendance['isNoPay']) {
                $noOfDaysNopay++;
                $noOfDaysAbsent++;
            }

            if ($attendance['isExpectedToPresent']) {
                $totalExpectedWorkTime += ($attendance['expectedWorkTime']) ? $attendance['expectedWorkTime'] : 0;
                if ($attendance['isHalfDayLeave']) {
                    $noOfDaysExpected += 0.5;
                    $noOfDaysAbsent += 0.5;
                } else {
                    $noOfDaysExpected++;
                }
            }

            if ($attendance['isPresent']) {
                if ($attendance['isHalfDayLeave']) {
                    $noOfDaysPresent += 0.5;
                } else {
                    $noOfDaysPresent++;
                }
            }
            $totalActualWorkTime += ($attendance['workTime']) ? $attendance['workTime'] : 0;

            if ($attendance['isFullDayLeave']) {
                $noOfDaysAbsent++;
            }
        }

        $dayTypeWiseCount = $this->getDayTypeWiseCount($fromDate, $toDate, $employeeRecord['employeeID']);

        $employeeRecord['dayTypeWiseCount'] = $dayTypeWiseCount;
        $employeeRecord['noOfDaysNopay'] = $noOfDaysNopay;
        $employeeRecord['noOfDaysExpected'] = $noOfDaysExpected;
        $employeeRecord['noOfDaysPresent'] = $noOfDaysPresent;
        $employeeRecord['noOfDaysAbsent'] = $noOfDaysAbsent;
        $employeeRecord['noOfDaysPayCut'] = $this->calculatePayCutDays($employeeRecord['employeeID'], $employeeRecord['dateOfAppoinment'], $fromDate, $toDate);
        $employeeRecord['payRollYear'] = Carbon::parse($toDate)->format('Y');
        $employeeRecord['payRollmonth'] = Carbon::parse($toDate)->format('m');
        $employeeRecord['lateHrs'] = 0;

        if ($totalExpectedWorkTime != 0 && $totalExpectedWorkTime > $totalActualWorkTime) {
            $minutes = $totalExpectedWorkTime - $totalActualWorkTime;
            $employeeRecord['lateHrs'] = $hours = intdiv($minutes, 60);
        }

        return $employeeRecord;
    }

    public function getDayTypeWiseCount($fromDate, $toDate, $employeeID) {

        $rangeMethod = $this->getConfigValue('range_method_for_day_type_wise_count_calculate');

        $startDate = null;
        $endDate = null;

        switch ($rangeMethod) {
            case 'CURRENT_RANGE':
                $startDate = $fromDate;
                $endDate = $toDate;
                break;
            case 'PREVIOUS_RANGE':
                $lowerStartDateObj = new DateTime($fromDate);
                $nextLowerStartDateObj = $lowerStartDateObj->sub(new DateInterval('P1M'));
                $startDate = $nextLowerStartDateObj->format('Y-m-d');


                $lowerEndDateObj = new DateTime($toDate);
                $nextLowerEndDateObj = $lowerEndDateObj->sub(new DateInterval('P1M'));
                $endDate = $nextLowerEndDateObj->format('Y-m-d');
                break;
            case 'NEXT_RANGE':
                $upperStartDateObj = new DateTime($fromDate);
                $nextUpperStartDateObj = $upperStartDateObj->add(new DateInterval('P1M'));
                $startDate = $nextUpperStartDateObj->format('Y-m-d');


                $upperEndDateObj = new DateTime($toDate);
                $nextUpperEndDateObj = $upperEndDateObj->add(new DateInterval('P1M'));
                $endDate = $nextUpperEndDateObj->format('Y-m-d');
                break;
            
            default:
                # code...
                break;
        }

        $baseDayTypeList = DB::table('baseDayType')->where('isActive', true)->get();
        $dayTypeWiseCountArray = [];

        if (!is_null($baseDayTypeList)) {
            foreach ($baseDayTypeList as $key => $value) {
                $dayTypeWiseCountArray[$value->name] = 0;
            }

            $dayTypeWiseCount = DB::table('attendance_summary')
                ->select('baseDayType.name', DB::raw('count(*) as total'))
                ->leftJoin('baseDayType', 'baseDayType.id', '=', 'attendance_summary.baseDayType')
                ->where('attendance_summary.employeeId', '=', $employeeID)
                ->whereBetween('date', array($startDate, $endDate))->groupBy('baseDayType')->get()->toArray();

            foreach ($dayTypeWiseCount as $countKey => $countObj) {
                if ($countObj->name != null) {
                    $dayTypeWiseCountArray[$countObj->name] = $countObj->total;
                }
            }
        }

        return $dayTypeWiseCountArray;
    }


    public function deriveDateOfResign($employeeID)
    {
        //get termination employment record
        $terminateRecord = DB::table('employeeJob')
            ->where('employeeJob.employeeJourneyType', '=', 'RESIGNATIONS')
            ->where('employeeJob.employeeId', '=', $employeeID)
            ->orderBy('employeeJob.effectiveDate', 'DESC')->first();

        if (is_null($terminateRecord)) {
            return null;
        }
        $terminateRecord = (array) $terminateRecord;

        return $terminateRecord['effectiveDate'];
    }

    public function calculatePayCutDays($employeeID, $hireDate, $startDate, $toDate)
    {

        $resignDateBasePayCutDays = 0;
        $hireDateBasePayCutDays = 0;
        $endDate = Carbon::parse($toDate)->addDays(1);
        $dateOfResign = $this->deriveDateOfResign($employeeID);
        $startDate = Carbon::parse($startDate)->format('Y-m-d');
        $endDate =  Carbon::parse($endDate)->format('Y-m-d');
        $toDate =  Carbon::parse($toDate)->format('Y-m-d');


        $formattedStartDate = Carbon::createFromFormat('Y-m-d', $startDate);

        //check whether resign date is between the given date range
        if (!empty($dateOfResign)) {
            $formattedEndDate = Carbon::createFromFormat('Y-m-d', $endDate);
            $resign =  Carbon::parse($dateOfResign)->format('Y-m-d');
            $formattedToDate = Carbon::createFromFormat('Y-m-d', $toDate);

            $formattedResignDate = Carbon::createFromFormat('Y-m-d', $resign);
            $isBetweenDateRange = $formattedResignDate->between($formattedStartDate, $formattedEndDate);

            if ($isBetweenDateRange) {
                $resignDateBasePayCutDays =  $formattedToDate->diffInDays($formattedResignDate);
            }
        }

        //check whether hire date is between the given date range
        if (!empty($hireDate)) {
            $appointmentDate =  Carbon::parse($hireDate)->format('Y-m-d');
            $formattedEndDate = Carbon::createFromFormat('Y-m-d', $endDate);

            $formattedAppointmentDate = Carbon::createFromFormat('Y-m-d', $appointmentDate);
            $isHireDateBetweenDateRange = $formattedAppointmentDate->between($formattedStartDate, $formattedEndDate);

            if ($isHireDateBetweenDateRange) {
                $hireDateBasePayCutDays =  $formattedStartDate->diffInDays($formattedAppointmentDate);
            }
        }

        $totalPayCutDays = $resignDateBasePayCutDays +  $hireDateBasePayCutDays;

        return $totalPayCutDays;
    }

    public function setAllowanceValues($fixedAllowanceFields, $relatedSalaryRecord)
    {
        $relatedSalaryRecord = (array) $relatedSalaryRecord;
        $allowanceArray = [];
        if (empty($relatedSalaryRecord)) {
            $allowanceArray = array_fill_keys($fixedAllowanceFields, 0);
        } else {
            foreach ($fixedAllowanceFields as $key => $field) {
                $allowanceArray[$field] = (empty($relatedSalaryRecord[$field])) ? 0 : $this->decrypt($relatedSalaryRecord[$field]);
            }
        }

        return $allowanceArray;
    }

    public function changeAttendanceRecordsStateForPayRoll($data)
    {
        try {

            $fromDate = $data['from'];
            $toDate = $data['to'];

            if (empty($data['state'])) {
                return $this->error(500, Lang::get('payRollMessages.basic.ERR_CHANGE_STATE_REQUIRED'), []);
            }

            if ($data['state'] != 'lock' && $data['state'] != 'unlock') {
                return $this->error(500, Lang::get('payRollMessages.basic.ERR_CHANGE_STATE_UNDEFINED'), []);
            }

            if (empty($fromDate) || empty($toDate)) {
                return $this->error(500, Lang::get('payRollMessages.basic.ERR_DATE_RANGE'), []);
            }


            if ($fromDate > $toDate) {
                return $this->error(500, Lang::get('payRollMessages.basic.ERR_TO_DATE_MUST_GREATER'), []);
            }

            $status = ($data['state'] == 'lock') ? true : false;

            $lockAttendanceRecods = DB::table('attendance_summary')
                ->whereBetween('date', array($fromDate, $toDate))
                ->update(['isLocked' => $status]);

            return $this->success(200, Lang::get('payRollMessages.basic.SUCC_ATTEDANCE_LOCK'), []);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('payRollMessages.basic.ERR_ATTEDANCE_LOCK'), null);
        }
    }


    private function getEmployeeRelatedSalaryDetails($employeeId)
    {
        $salaryDetails = [];
        $relatedSalaryRecords = DB::table('employeeSalary')->where('employeeId', '=', $employeeId)->get();

        if (sizeof($relatedSalaryRecords) > 0) {

            foreach ($relatedSalaryRecords as $key => $salaryRecord) {
                $salaryRecord  = (array)$salaryRecord;
                $basicSalary = 0;
                $isSetBasic = false;
                $fixedAllowance = [];
                $salaryComponantsDetails = (array) json_decode($salaryRecord['salaryDetails']);

                if (sizeof($salaryComponantsDetails) > 0) {
                    foreach ($salaryComponantsDetails as $key => $value) {
                        $value = (array) $value;

                        $relatedSalaryComponent = DB::table('salaryComponents')->where('id', '=', $value['salaryComponentId'])->first();
                        if (!is_null($relatedSalaryComponent)) {
                            if (!$isSetBasic && $relatedSalaryComponent->salaryType == 'BASE_PAY') {
                                $basicSalary = $value['value'];
                                $isSetBasic = true;
                            }

                            if ($relatedSalaryComponent->salaryType == 'FIXED_ALLOWANCE') {
                                $fixedAllowance[$relatedSalaryComponent->name] = $value['value'];
                            }
                        }
                    }
                }

                $tempSalaryArr = [
                    "id" => $salaryRecord['id'],
                    "basicSalary" => $basicSalary,
                    "fixedAllowance" => $fixedAllowance,
                    "effectiveDate" => $salaryRecord['effectiveDate'],
                    "createdAt" => $salaryRecord['createdAt'],
                    "updatedAt" => $salaryRecord['updatedAt']
                ];
                $salaryDetails[] = $tempSalaryArr;
            }
        }
        return $salaryDetails;
    }

    private function getEmployeeOrgStructureDetails($employeeId, $currentJobId)
    {
        $orgStructureDetails = [];
        $relatedJob = DB::table('employeeJob')->where('id', '=', $currentJobId)->first();
        $orgHierarchyConfig = (array) $this->getConfigValue('organization_hierarchy');

        if (!is_null($relatedJob) && !empty($relatedJob->orgStructureEntityId)) {
            $orgEntityId = $relatedJob->orgStructureEntityId;
            $entityDetails = (array) $this->getEntityDetails($relatedJob->orgStructureEntityId);

            foreach ($orgHierarchyConfig as $levelKey => $configValue) {
                $orgStructureDetails[$levelKey] = isset($entityDetails[$configValue]) ? $entityDetails[$configValue]->name : '-';
            }
            
        }

        $dataSet['orgLevelHierarchyConfig'][] = $orgHierarchyConfig;
        $dataSet['orgStructureDetails'][] = $orgStructureDetails;
            
        $data[] = $dataSet;

        return $data;
    }

    public function getEntityDetails($id)
    {
        try {
            $orgHierarchyConfig = (array) $this->getConfigValue('organization_hierarchy');

            $entities = $this->store->getFacade()::table($this->orgEntityModel->getName())
                ->where('isDelete', false)
                ->get(['id', 'name', 'parentEntityId', 'entityLevel', 'headOfEntityId'])
                ->toArray();

            $nextId = $id;
            $response = [];

            do {
                $index = array_search($nextId, array_column($entities, 'id'));
                $entity = $entities[$index] ?? null;

                if (is_null($entity)) break;

                $response[$orgHierarchyConfig[$entity->entityLevel]] = $entity;
                $nextId = $entity->parentEntityId;
            } while ($nextId != null);

            return $response;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return [];
        }
    }

    private function validateAttachments($data)
    {
        foreach ($data['attachments'] as $attachment) {
            $fileName = $attachment['name'];

            $fileNameArr = explode('_', $fileName);

            if (count($fileNameArr) < 2) {
                return false;
            }

            list($employeeId, $payMonthString) = $fileNameArr;

            $payMonthObj = Carbon::createFromFormat('Y-m', $payMonthString);

            if (empty($employeeId) || !$payMonthObj) {
                return false;
            }
        }

        return true;
    }

    public function uploadPayslips($data)
    {
        try {

            $isValid = $this->validateAttachments($data);

            if (!$isValid) {
                return $this->error(400, Lang::get('payRollMessages.basic.ERR_SLIP_FILE_NAME'), null);
            }

            $paySlips = [];

            foreach ($data['attachments'] as $attachment) {
                $fileName = $attachment['name'];
                $content = $attachment['content'];

                list($employeeId, $payMonthString) = explode('_', $fileName);

                $payMonthObj = Carbon::createFromFormat('Y-m', $payMonthString);

                $fileSize = (int) (strlen(rtrim($content, '=')) * 3 / 4);
                $fileContent = 'data:application/pdf;base64,' . $content;

                $file = $this->fileStore->putBase64EncodedObject($fileName, $fileSize, $fileContent);

                if ($file) {
                    $paySlips[] = [
                        'employeeId' => $employeeId,
                        'payMonth' => $payMonthObj,
                        'fileStoreObjectId' => $file->id
                    ];
                }
            }

            DB::table('payslip')->insert($paySlips);

            return $this->success(200, Lang::get('payRollMessages.basic.SUCC_SLIP_UPLOAD'), []);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('payRollMessages.basic.ERR_SLIP_UPLOAD'), null);
        }
    }

    public function getRecentPayslips()
    {
        try {
            $employee = $this->session->getEmployee();
            $employeeId = ($employee) ? $employee->id : null;

            if (is_null($employeeId)) {
                return $this->success(200, Lang::get('payRollMessages.basic.SUCC_GET_RECENT_PAYSLIPS'), []);
            }

            $recentPaySlips = DB::table('payslip')->where('employeeId', '=', $employeeId)->orderByDesc('payMonth')->get();

            return $this->success(200, Lang::get('payRollMessages.basic.SUCC_GET_RECENT_PAYSLIPS'), $recentPaySlips);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('payRollMessages.basic.ERR_GET_RECENT_PAYSLIPS'), null);
        }
    }

    public function getPayslip($id)
    {
        try {

            if (is_null($id)) {
                return $this->error(400, Lang::get('payRollMessages.basic.ERR_PAYSLIP_ID_REQUIRED'), null);
            }

            $employee = $this->session->getEmployee();
            $employeeId = ($employee) ? $employee->id : null;

            if (is_null($employeeId)) {
                return $this->error(400, Lang::get('payRollMessages.basic.ERR_PAYSLIP_EMP_PROF_NOT_EXIST'), null);
            }

            $paySlip = DB::table('payslip')->where('id', '=', $id)->where('employeeId', '=', $employeeId)->first();

            if (!$paySlip) {
                return $this->error(404, Lang::get('payRollMessages.basic.ERR_PAYSLIP_NOT_EXIST'), null);
            }

            $paySlip->fileObject = $this->fileStore->getBase64EncodedObject($paySlip->fileStoreObjectId);

            return $this->success(200, Lang::get('payRollMessages.basic.SUCC_GET_PAYSLIP'), $paySlip);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('payRollMessages.basic.ERR_GET_PAYSLIP'), null);
        }
    }
}
