<?php

namespace App\Traits;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait LeaveAccrual
{
    use JobResponser;
    use EmployeeHelper;

    public function accrualProcess($dateObject, $isManualAccrual = false, $manualProcessId = null)
    {
        try {
            // get company details
            $company = DB::table('company')->first(['timeZone', 'leavePeriodStartingMonth', 'leavePeriodEndingMonth']);

            // get all locations
            $locations = DB::table('location')->get(['id', 'timeZone']);

            $leaveAccruals = $this->getActiveLeaveAccruals();

            foreach ($leaveAccruals as $leaveAccrual) {
                var_dump($leaveAccrual);
                // for MONTHLY allocation
                if ($leaveAccrual->accrualFrequency == "MONTHLY") {
                    $this->employeeLeaveAccrual($dateObject, $leaveAccrual, $company, $locations, $isManualAccrual, $manualProcessId);
                } elseif ($leaveAccrual->accrualFrequency == "ANNUAL") {
                    $this->employeeAnnualLeaveAccrual($dateObject, $leaveAccrual, $company, $locations, $isManualAccrual, $manualProcessId);
                }
            }

            return $this->jobResponse(false);
        } catch (Exception $e) {
            return $this->jobResponse(true, $e->getMessage());
        }
    }

    private function employeeLeaveAccrual($dateObject, $leaveAccrualObject, $company, $locations, $isManualAccrual, $manualProcessId)
    {
        try {

            DB::beginTransaction();

            $leaveAccrualProcess = [
                'leaveAccrualId' => $leaveAccrualObject->id,
                'method' => ($isManualAccrual) ? 'MANUAL' : 'AUTOMATE',
                'numberOfAllocatedEntitlements' => 0,
                'manualProcessId' => $manualProcessId
            ];

            // create process
            $processId = DB::table('leaveAccrualProcess')->insertGetId($leaveAccrualProcess);

            // get group employees
            $employees = $this->getEmployeeIdsByLeaveGroupId($leaveAccrualObject->leaveEmployeeGroupId);

            // get current date according to company
            $companyDate = ($isManualAccrual) ? $dateObject->copy() : $dateObject->copy()->tz($company->timeZone);

            $numOfAllocatedEntitlements = 0;

            foreach ($employees as $employee) {
                $actualHireDate = !empty($employee->recentHireDate) ? $employee->recentHireDate : $employee->hireDate;

                var_dump("Employee Id >");
                var_dump($employee->id);
                var_dump("Employee Hire Date >");
                // var_dump($employee->hireDate);
                var_dump($actualHireDate);

                $hireDateObj = Carbon::createFromFormat('Y-m-d', $actualHireDate, $company->timeZone);
                // ignore feautre hire dated employees
                // if ($hireDateObj->greaterThan($dateObject)) {
                //     continue;
                // }
                if ($hireDateObj->copy()->startOfDay()->greaterThan($dateObject->copy()->startOfDay())) {
                    continue;
                }

                // get employee current job
                $employeeJob = $this->getEmployeeJob($employee->id, $companyDate->format('Y-m-d'), ['locationId', 'employmentStatusId', 'effectiveDate']);

                // check whether employee has a job, if job not exist ignore that employee
                if (empty($employeeJob)) {
                    continue;
                }

                // get location
                $locationId = empty($employeeJob->locationId) ? null : $employeeJob->locationId;

                // ignore employee if location not exist
                if (empty($locationId)) {
                    continue;
                }

                //get employementStatus
                $employmentStatusId = empty($employeeJob->employmentStatusId) ? null : $employeeJob->employmentStatusId;

                // ignore employee if location not exist
                if (empty($employmentStatusId)) {
                    continue;
                }

                //get employementStatus
                $employmentStatusDetail = DB::table('employmentStatus')->where('id', $employmentStatusId)->first();

                // if ($employmentStatusDetail->category == 'CONTRACT') {
                //     $hireDateObj = Carbon::createFromFormat('Y-m-d', $employeeJob->effectiveDate);
                // } 

                //check whether accrual is allocated only for joined year
                if ($leaveAccrualObject->isAllocatedOnlyForJoinedYear) {
                    $hireDateYearEndObj = $hireDateObj->copy()->endOfYear();

                    if ($dateObject->copy()->startOfDay()->greaterThan($hireDateYearEndObj->startOfDay())) {
                        continue;
                    }
                }


                if ($leaveAccrualObject->leavePeriod == "STANDARD") {
                    if ($employmentStatusDetail->category == 'PROBATION' || $employmentStatusDetail->category == 'CONTRACT') {
    
                        if (!empty($employmentStatusDetail->period) && !empty($employmentStatusDetail->periodUnit)) {
    
                            $empStatusEffectiveFromObj = Carbon::parse($employeeJob->effectiveDate);
                            $empStatusEffectiveToObj = null;
                            $accrualdate = $companyDate->format('Y-m-d');
                            $accrualdateObj = Carbon::parse($accrualdate);
    
                            switch ($employmentStatusDetail->periodUnit) {
                                case 'YEARS':
                                    $empStatusEffectiveToObj = $empStatusEffectiveFromObj->copy()->addYears($employmentStatusDetail->period);
                                    break;
                                case 'MONTHS':
                                    $empStatusEffectiveToObj = $empStatusEffectiveFromObj->copy()->addMonths($employmentStatusDetail->period);
                                    break;
                                case 'DAYS':
                                    $empStatusEffectiveToObj = $empStatusEffectiveFromObj->copy()->addDays($employmentStatusDetail->period);
                                    break;
                                default:
                                    # code...
                                    break;
                            }
    
                            //check date object is within the employeeStatusEffectiveRange
                            if ($leaveAccrualObject->dayOfCreditingForMonthlyFrequency === 'MONTHLY_HIRE_DATE' || $leaveAccrualObject->dayOfCreditingForMonthlyFrequency === 'FIRST_ACCRUE_ON_HIRE_DATE_AND_OTHERS_ON_FIRST_DAY_OF_MONTH') {
                                if (!($accrualdateObj->copy()->startOfDay()->greaterThanOrEqualTo($empStatusEffectiveFromObj->copy()->startOfDay()) && $accrualdateObj->copy()->startOfDay()->lessThan($empStatusEffectiveToObj->copy()->startOfDay()))) {
                                    continue;
                                }
                            } else {
                                if (!($accrualdateObj->copy()->startOfDay()->greaterThan($empStatusEffectiveFromObj->copy()->startOfDay()) && $accrualdateObj->copy()->startOfDay()->lessThan($empStatusEffectiveToObj->copy()->startOfDay()))) {
                                    continue;
                                }
                            }

                            
                        }
                    }
                } else if ($leaveAccrualObject->leavePeriod == "HIRE_DATE_BASED") {

                    if ($employmentStatusDetail->category == 'PROBATION' || $employmentStatusDetail->category == 'CONTRACT') {
                        if (!empty($employmentStatusDetail->periodUnit) && !empty($employmentStatusDetail->period)) {
                            $leavePeriod['from'] = $hireDateObj->copy()->format('Y-m-d');
                            $leavePeriod['to'] = null;
                            $fromDateObj = $hireDateObj->copy();
                            $toDateObj = null;
                            $accrualdate = $companyDate->format('Y-m-d');
                            $accrualdateObj = Carbon::parse($accrualdate);
    
                            $hiredMonth = $hireDateObj->copy()->isoFormat('MM');
                            $hiredDay = $hireDateObj->copy()->isoFormat('DD');
        
                            switch ($employmentStatusDetail->periodUnit) {
                                case 'YEARS':
                                    $toDateObj = $fromDateObj->copy()->addYears($employmentStatusDetail->period)->subDay();
                                    break;
                                case 'MONTHS':
                                    $toDateObj = $fromDateObj->copy()->addMonths($employmentStatusDetail->period)->subDay();
                                    break;
                                case 'DAYS':
                                    $toDateObj = $fromDateObj->copy()->addDays($employmentStatusDetail->period)->subDay();
                                    break;
                                default:
                                    # code...
                                    break;
                            }
    
                            //check date object is within the employeeStatusEffectiveRange
                            if (!$accrualdateObj->copy()->startOfDay()->between($fromDateObj->copy()->startOfDay(),$toDateObj->copy()->startOfDay())) {
                                continue;
                            }

                            // if (!($accrualdateObj->copy()->startOfDay()->greaterThan($fromDateObj->copy()->startOfDay()) && $accrualdateObj->copy()->startOfDay()->lessThan($toDateObj->copy()->startOfDay()))) {
                            //     continue;
                            // }
    
                        } 
                    }

                }
                
                // if location not exist get default company timezone
                $timeZone = $locations->firstWhere('id', $locationId)->timeZone;

                $locationDateObj = ($isManualAccrual) ? $dateObject->copy() : $dateObject->copy()->tz($timeZone);

                // check accrual exist
                $accruedEmployees = DB::table('leaveAccrualEntitlementLog')
                    ->where('employeeId', '=', $employee->id)
                    ->where('leaveTypeId', '=', $leaveAccrualObject->leaveTypeId)
                    ->whereDate('accrualDate', '=', $locationDateObj->format('Y-m-d'))
                    ->first('id');


                if (!is_null($accruedEmployees)) {
                    continue;
                }

                $employeeEntitlement = $this->getAccrualEntitlements($locationDateObj, $employee, $leaveAccrualObject, $company, $employmentStatusDetail, $employeeJob);

                $entitlementId = null;

                if (!empty($employeeEntitlement)) {
                    $numOfAllocatedEntitlements += 1;
                    // add entitlement
                    $entitlementId = DB::table('leaveEntitlement')->insertGetId($employeeEntitlement);
                }

                $leaveAccrualEntitlementLog = [
                    'leaveAccrualProcessId' => $processId,
                    'leaveTypeId' => $leaveAccrualObject->leaveTypeId,
                    'employeeId' => $employee->id,
                    'leaveEntitlementId' => $entitlementId,
                    'accrualDate' => $locationDateObj->format('Y-m-d')
                ];

                // create entitlement log
                DB::table('leaveAccrualEntitlementLog')->insert($leaveAccrualEntitlementLog);
            }

            // update process
            if ($numOfAllocatedEntitlements > 0) {
                DB::table('leaveAccrualProcess')->where('id', $processId)->update(['numberOfAllocatedEntitlements' => $numOfAllocatedEntitlements]);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    private function employeeAnnualLeaveAccrual($dateObject, $leaveAccrualObject, $company, $locations, $isManualAccrual, $manualProcessId)
    {
        try {

            DB::beginTransaction();
            $leaveAccrualProcess = [
                'leaveAccrualId' => $leaveAccrualObject->id,
                'method' => ($isManualAccrual) ? 'MANUAL' : 'AUTOMATE',
                'numberOfAllocatedEntitlements' => 0,
                'manualProcessId' => $manualProcessId
            ];

            // create process
            $processId = DB::table('leaveAccrualProcess')->insertGetId($leaveAccrualProcess);

            // get group employees
            $employees = $this->getEmployeeIdsByLeaveGroupId($leaveAccrualObject->leaveEmployeeGroupId);

            // get current date according to company
            $companyDate = ($isManualAccrual) ? $dateObject->copy() : $dateObject->copy()->tz($company->timeZone);

            $numOfAllocatedEntitlements = 0;

            foreach ($employees as $employee) {
                $actualHireDate = !empty($employee->recentHireDate) ? $employee->recentHireDate : $employee->hireDate;

                var_dump("Employee Id >");
                var_dump($employee->id);
                var_dump("Employee Hire Date >");
                // var_dump($employee->hireDate);
                var_dump($actualHireDate);

                $hireDateObj = Carbon::createFromFormat('Y-m-d', $actualHireDate, $company->timeZone);

                // ignore feautre hire dated employees
                if ($hireDateObj->copy()->startOfDay()->greaterThan($dateObject->copy()->startOfDay())) {
                    continue;
                }
                
                // get employee current job
                $employeeJob = $this->getEmployeeJob($employee->id, $companyDate->format('Y-m-d'), ['locationId']);
                
                // check whether employee has a job, if job not exist ignore that employee
                if (empty($employeeJob)) {
                    continue;
                }

                // get location
                $locationId = empty($employeeJob->locationId) ? null : $employeeJob->locationId;

                // ignore employee if location not exist
                if (empty($locationId)) {
                    continue;
                }

                
                // if location not exist get default company timezone
                $timeZone = $locations->firstWhere('id', $locationId)->timeZone;

                $locationDateObj = ($isManualAccrual) ? $dateObject->copy() : $dateObject->copy()->tz($timeZone);

                // check accrual exist
                $accruedEmployees = DB::table('leaveAccrualEntitlementLog')
                    ->where('employeeId', '=', $employee->id)
                    ->where('leaveTypeId', '=', $leaveAccrualObject->leaveTypeId)
                    ->whereDate('accrualDate', '=', $locationDateObj->format('Y-m-d'))
                    ->first('id');


                if (!is_null($accruedEmployees)) {
                    continue;
                }
                
                $employeeEntitlement = $this->getAccrualEntitlementsForAnnualAccrual($locationDateObj, $employee, $leaveAccrualObject, $company);

                $entitlementId = null;

                if (!empty($employeeEntitlement)) {
                    $numOfAllocatedEntitlements += 1;
                    // add entitlement
                    $entitlementId = DB::table('leaveEntitlement')->insertGetId($employeeEntitlement);
                }

                $leaveAccrualEntitlementLog = [
                    'leaveAccrualProcessId' => $processId,
                    'leaveTypeId' => $leaveAccrualObject->leaveTypeId,
                    'employeeId' => $employee->id,
                    'leaveEntitlementId' => $entitlementId,
                    'accrualDate' => $locationDateObj->format('Y-m-d')
                ];

                // create entitlement log
                DB::table('leaveAccrualEntitlementLog')->insert($leaveAccrualEntitlementLog);
            }

            // update process
            if ($numOfAllocatedEntitlements > 0) {
                DB::table('leaveAccrualProcess')->where('id', $processId)->update(['numberOfAllocatedEntitlements' => $numOfAllocatedEntitlements]);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    private function getAccrualEntitlementsForAnnualAccrual($dateObject, $employeeObj, $leaveAccrualObject, $company)
    {
        $currentMonth = $dateObject->isoFormat('M');
        $currentFullMonth = $dateObject->isoFormat('MM');
        $currentDay = $dateObject->isoFormat('DD');
        $currentYear = $dateObject->format('Y');
        $actualHireDate = !empty($employeeObj->recentHireDate) ? $employeeObj->recentHireDate : $employeeObj->hireDate;
        $hireDateObj = Carbon::createFromFormat('Y-m-d', $actualHireDate);
        $hireFullMonth = $hireDateObj->isoFormat('MM');
        $hireDay = $hireDateObj->isoFormat('DD');

        var_dump("getAccrualEntitlementsForAnnualAccrual employee id > " . $employeeObj->id);

        if ($leaveAccrualObject->leavePeriod == "STANDARD") {
            $allowToAccrue = false;
            $allocatedLeaveCount = 0;
            // get accrual execution month and date
            $frequencyRule = $leaveAccrualObject->dayOfCreditingForAnnualFrequency;
            $combineCurrentDateMonth = $currentFullMonth.'-'.$currentDay;

            // check month validation
            if ($combineCurrentDateMonth == $frequencyRule) {
                
                $frequencyRuleSplitArr = explode("-",$frequencyRule);
                $frequencyRuleMonth = $frequencyRuleSplitArr[0];
                $firstDayOfHiredMonth = $hireDateObj->copy()->startOfMonth();
                $hiredYear = $hireDateObj->format('Y');
                $annualAccrualDayOfHiredYear = $hiredYear.'-'.$frequencyRule;
                $annualAccrualDayOfHiredYearObj = Carbon::createFromFormat('Y-m-d', $annualAccrualDayOfHiredYear);

                if ($annualAccrualDayOfHiredYearObj->isSameDay($hireDateObj)) {
                    $firstAccrualDateObj = $hireDateObj;
                } else {
                    $firstAccrualDateObj = $annualAccrualDayOfHiredYearObj->copy()->addYear();
                }
                $firstAccrualFrequencyData =  $this->checkFirstAccrualForAnnualFrequency($leaveAccrualObject->firstAccrualForAnnualfrequency, $dateObject, $firstAccrualDateObj, $hireDateObj, $leaveAccrualObject);

                $allowToAccrue = ($firstAccrualFrequencyData['status']);
                $allocatedLeaveCount = ($firstAccrualFrequencyData['status']) ? $firstAccrualFrequencyData['leaveCount'] : 0;

            }

            if ($allowToAccrue) {
                var_dump('allow to accrue > STANDARD > ' . $employeeObj->id);

                $leavePeriod = $this->standardLeavePeriod($dateObject, $company);

                var_dump($leavePeriod);

                $leaveValidityPeriod = $this->leaveValidityPeriod($dateObject, $leavePeriod, $leaveAccrualObject->accrualValidFrom);

                var_dump($leaveValidityPeriod);

                $entitlement = [
                    'employeeId' => $employeeObj->id,
                    'leaveTypeId' => $leaveAccrualObject->leaveTypeId,
                    'leavePeriodFrom' => $leavePeriod['from'],
                    'leavePeriodTo' => $leavePeriod['to'],
                    'validFrom' => $leaveValidityPeriod['from'],
                    'validTo' => $leaveValidityPeriod['to'],
                    'entilementCount' => $allocatedLeaveCount,
                    'comment' => null,
                ];

                return $this->getLeaveEntitlement($entitlement);
            } else {
                return [];
            }
        } else if ($leaveAccrualObject->leavePeriod == "HIRE_DATE_BASED") {
            $allowToAccrue = false;
            // get accrual execution month and date
            $combineCurrentDateMonth = $currentFullMonth.'-'.$currentDay;
            $combineHireDateMonth = $hireFullMonth.'-'.$hireDay;
            $dueHireDateBaseAccrualDateObj = $this->getRelaventHireDateBaseAccrualDateForGivenYear($currentYear, $hireDateObj);
            
            // check month and date validation
            if ($dueHireDateBaseAccrualDateObj->isSameDay($dateObject)) {
                $firstAccrualDateObj = $hireDateObj;
                $firstAccrualFrequencyData =  $this->checkFirstAccrualForAnnualFrequency($leaveAccrualObject->firstAccrualForAnnualfrequency, $dateObject, $firstAccrualDateObj, $hireDateObj, $leaveAccrualObject);
                $allowToAccrue = ($firstAccrualFrequencyData['status']);
                $allocatedLeaveCount = ($firstAccrualFrequencyData['status']) ? $firstAccrualFrequencyData['leaveCount'] : 0;
            }

            if ($allowToAccrue) {
                var_dump('HIRE_DATE_BASED ok  >>> ' . $employeeObj->id);
                $leavePeriod = $this->hireDateLeavePeriodForAnnualAccrual($dateObject, $hireDateObj);
                var_dump($leavePeriod);

                $leaveValidityPeriod = $this->leaveValidityPeriod($dateObject, $leavePeriod, $leaveAccrualObject->accrualValidFrom);

                $entitlement = [
                    'employeeId' => $employeeObj->id,
                    'leaveTypeId' => $leaveAccrualObject->leaveTypeId,
                    'leavePeriodFrom' => $leavePeriod['from'],
                    'leavePeriodTo' => $leavePeriod['to'],
                    'validFrom' => $leaveValidityPeriod['from'],
                    'validTo' => $leaveValidityPeriod['to'],
                    'entilementCount' => $leaveAccrualObject->amount,
                    'comment' => null,
                ];
                return $this->getLeaveEntitlement($entitlement);
            } else {
                return [];
            }
        }
    }

    private function getRelaventHireDateBaseAccrualDateForGivenYear($year, $hireDateObj)
    {
        $hireDateYearMonth = $hireDateObj->format('Y-m');
        $hireDay = $hireDateObj->format('d');
        $hireDateYearMonthObj = Carbon::createFromFormat('Y-m', $hireDateYearMonth);
        $hireDateYear = $hireDateObj->format('Y');
        $yearDiff = (int)$year - (int) $hireDateYear;

        if ($yearDiff > 0) {
            $currentYearDayMonthStartDay = $hireDateYearMonthObj->startOfMonth()->addYears($yearDiff);
            $daysInMonth = $currentYearDayMonthStartDay->daysInMonth;
            $days = ($daysInMonth < $hireDay) ? $daysInMonth : $hireDay;
            $givenYearAnniversary = $currentYearDayMonthStartDay->copy()->addDays($days - 1);
        } else {
            $currentYearDayMonthStartDay = $hireDateYearMonthObj->startOfMonth();
            $daysInMonth = $currentYearDayMonthStartDay->daysInMonth;
            $days = ($daysInMonth < $hireDay) ? $daysInMonth : $hireDay;
            $givenYearAnniversary = $currentYearDayMonthStartDay->copy()->addDays($days - 1);
        }

        return $givenYearAnniversary;

    }

    private function getAccrualEntitlements($dateObject, $employeeObj, $leaveAccrualObject, $company, $employmentStatusDetail, $employeeJob)
    {
        $currentMonth = $dateObject->isoFormat('M');
        $currentDay = $dateObject->isoFormat('DD');

        $actualHireDate = !empty($employeeObj->recentHireDate) ? $employeeObj->recentHireDate : $employeeObj->hireDate;
        $dateOfJoin = $employeeObj->hireDate;

        // if ($employmentStatusDetail->category == 'CONTRACT') {
        //     $hireDateObj = Carbon::createFromFormat('Y-m-d', $employeeJob->effectiveDate);
        // } else {
        //     $hireDateObj = Carbon::createFromFormat('Y-m-d', $employeeObj->hireDate);
        // }
        $hireDateObj = Carbon::createFromFormat('Y-m-d', $actualHireDate);

        var_dump("getAccrualEntitlements employee id > " . $employeeObj->id);

        if ($leaveAccrualObject->leavePeriod == "STANDARD") {
            $allowToAccrue = false;
            // get accrual execution months
            $frequencyRule = $leaveAccrualObject->accrueEvery;

            // check month validation
            if ($currentMonth % $frequencyRule == 0) {
                // check day validation
                $allowToAccrue = $this->checkCreditingForMonthlyFrequency($leaveAccrualObject, $dateObject, $hireDateObj, $currentDay, $dateOfJoin);
            }

            if ($allowToAccrue) {
                var_dump('allow to accrue > STANDARD > ' . $employeeObj->id);

                //handle leave period According to the employement status category
                if($employmentStatusDetail->category == 'PERMANENT') {
                    $leavePeriod = $this->standardLeavePeriod($dateObject, $company);
                } else if ($employmentStatusDetail->category == 'CONTRACT') {
                    if (!empty($employmentStatusDetail->periodUnit) && !empty($employmentStatusDetail->period)) {
                        $leavePeriod['from'] = $employeeJob->effectiveDate;
                        $leavePeriod['to'] = null;
                        $fromDateObj = Carbon::parse($employeeJob->effectiveDate);
                        $toDateObj = null;
    
                        switch ($employmentStatusDetail->periodUnit) {
                            case 'YEARS':
                                $toDateObj = $fromDateObj->copy()->addYears($employmentStatusDetail->period)->subDay();
                                break;
                            case 'MONTHS':
                                $toDateObj = $fromDateObj->copy()->addMonths($employmentStatusDetail->period)->subDay();
                                break;
                            case 'DAYS':
                                $toDateObj = $fromDateObj->copy()->addDays($employmentStatusDetail->period)->subDay();
                                break;
                            default:
                                # code...
                                break;
                        }
                        $leavePeriod['to'] = $toDateObj->format('Y-m-d');

                    } else {
                        $leavePeriod = $this->standardLeavePeriod($dateObject, $company);
                    }
                } else if ($employmentStatusDetail->category == 'PROBATION') {
                    if (!empty($employmentStatusDetail->periodUnit) && !empty($employmentStatusDetail->period)) {
                        $leavePeriod = $this->standardLeavePeriod($dateObject, $company);

                        $hiringYear = $hireDateObj->copy()->format('Y');
                        $currentYear = $dateObject->copy()->format('Y');

                        if ($hiringYear ==  $currentYear) {
                            $leavePeriod['from'] = $employeeJob->effectiveDate;
                        }

                        // $leavePeriod['from'] = $employeeJob->effectiveDate;

                        // $leavePeriod['to'] = $hireDateObj->copy()->endOfYear();

                    } else {
                        $leavePeriod = $this->standardLeavePeriod($dateObject, $company);
                    }
                }


                var_dump($leavePeriod);

                $leaveValidityPeriod = $this->leaveValidityPeriod($dateObject, $leavePeriod, $leaveAccrualObject->accrualValidFrom);

                var_dump($leaveValidityPeriod);

                $entitlement = [
                    'employeeId' => $employeeObj->id,
                    'leaveTypeId' => $leaveAccrualObject->leaveTypeId,
                    'leavePeriodFrom' => $leavePeriod['from'],
                    'leavePeriodTo' => $leavePeriod['to'],
                    'validFrom' => $leaveValidityPeriod['from'],
                    'validTo' => $leaveValidityPeriod['to'],
                    'entilementCount' => $leaveAccrualObject->amount,
                    'comment' => null,
                ];

                return $this->getLeaveEntitlement($entitlement);
            } else {
                return [];
            }
        } else if ($leaveAccrualObject->leavePeriod == "HIRE_DATE_BASED") {
            $allowToAccrue = false;
            // get accrual execution months
            $frequencyRule = $leaveAccrualObject->accrueEvery;

            // check month validation
            if ($currentMonth % $frequencyRule == 0) {
                // check day validation
                $allowToAccrue = $this->checkCreditingForMonthlyFrequency($leaveAccrualObject, $dateObject, $hireDateObj, $currentDay, $dateOfJoin);
            }

            if ($allowToAccrue) {
                var_dump('HIRE_DATE_BASED ok  >>> ' . $employeeObj->id);
                if($employmentStatusDetail->category == 'PERMANENT') {
                    $leavePeriod = $this->hireDateLeavePeriod($dateObject, $hireDateObj);
                } else if ($employmentStatusDetail->category == 'PROBATION' || $employmentStatusDetail->category == 'CONTRACT') {
                    if (!empty($employmentStatusDetail->periodUnit) && !empty($employmentStatusDetail->period)) {
                        $leavePeriod['from'] = $hireDateObj->copy()->format('Y-m-d');
                        $leavePeriod['to'] = null;
                        $fromDateObj = $hireDateObj->copy();
                        $toDateObj = null;

                        $hiredMonth = $hireDateObj->copy()->isoFormat('MM');
                        $hiredDay = $hireDateObj->copy()->isoFormat('DD');
    
                        switch ($employmentStatusDetail->periodUnit) {
                            case 'YEARS':
                                $toDateObj = $fromDateObj->copy()->addYears($employmentStatusDetail->period)->subDay();
                                break;
                            case 'MONTHS':
                                $toDateObj = $fromDateObj->copy()->addMonths($employmentStatusDetail->period)->subDay();
                                break;
                            case 'DAYS':
                                $toDateObj = $fromDateObj->copy()->addDays($employmentStatusDetail->period)->subDay();
                                break;
                            default:
                                # code...
                                break;
                        }

                        $leavePeriod['to'] = $toDateObj->format('Y-m-d');

                    } else {
                        $leavePeriod = $this->hireDateLeavePeriod($dateObject, $hireDateObj);
                    }
                }

                $leaveValidityPeriod = $this->leaveValidityPeriod($dateObject, $leavePeriod, $leaveAccrualObject->accrualValidFrom);

                $entitlement = [
                    'employeeId' => $employeeObj->id,
                    'leaveTypeId' => $leaveAccrualObject->leaveTypeId,
                    'leavePeriodFrom' => $leavePeriod['from'],
                    'leavePeriodTo' => $leavePeriod['to'],
                    'validFrom' => $leaveValidityPeriod['from'],
                    'validTo' => $leaveValidityPeriod['to'],
                    'entilementCount' => $leaveAccrualObject->amount,
                    'comment' => null,
                ];
                return $this->getLeaveEntitlement($entitlement);
            } else {
                return [];
            }
        }
    }

    private function checkCreditingForMonthlyFrequency($leaveAccrualObject, $dateObject, $hireDateObj, $currentDay, $dateOfJoin)
    {
        var_dump('> checkCreditingForMonthlyFrequency');
        switch ($leaveAccrualObject->dayOfCreditingForMonthlyFrequency) {
            case 'FIRST_DAY':
                var_dump('> FIRST_DAY');
                $startDateObj = $dateObject->copy()->startOfMonth();
                // to check whether current day is 1st
                if ($startDateObj->isoFormat('DD') == $currentDay) {
                    // to check first accrual rule
                    $firstDayOfHiredMonth = $hireDateObj->copy()->startOfMonth();
                    if ($firstDayOfHiredMonth->isSameDay($hireDateObj)) {
                        $firstAccrualDateObj = $hireDateObj;
                    } else {
                        $firstAccrualDateObj = $firstDayOfHiredMonth->copy()->addMonth();
                    }
                    return $this->checkFirstAccrualForMonthlyFrequency($leaveAccrualObject->firstAccrualForMonthlyFrequency, $dateObject, $firstAccrualDateObj, $hireDateObj);
                }
                break;
            case 'LAST_DAY':
                var_dump('> LAST_DAY');
                $endDateObj = $dateObject->copy()->endOfMonth();
                // to check whether current day is last day of month
                if ($endDateObj->isoFormat('DD') == $currentDay) {
                    // to check first accrual rule
                    $lastDayOfHiredMonth = $hireDateObj->copy()->endOfMonth();
                    if ($lastDayOfHiredMonth->isSameDay($hireDateObj)) {
                        $firstAccrualDateObj = $hireDateObj;
                    } else {
                        $firstAccrualDateObj = $lastDayOfHiredMonth->copy();
                    }
                    return $this->checkFirstAccrualForMonthlyFrequency($leaveAccrualObject->firstAccrualForMonthlyFrequency, $dateObject, $firstAccrualDateObj, $hireDateObj);
                }
                break;
            case 'MONTHLY_HIRE_DATE':
                var_dump('> MONTHLY_HIRE_DATE');
                $currentMonth = $dateObject->format('m');
                $currentYear = $dateObject->format('Y');
                $hiredateString = $hireDateObj->format('d');
                $currentmonthHiredate = $currentYear.'-'.$currentMonth.'-'.$hiredateString;
                $currentmonthHireDateObj = Carbon::parse($currentmonthHiredate);
                if (Carbon::parse($currentmonthHiredate)->format('Y-m-d') === $currentmonthHiredate) {
                    if ($hireDateObj->isoFormat('DD') == $currentDay) {
                        // first accrual date
                        $firstAccrualDateObj = $hireDateObj->copy();
                        return $this->checkFirstAccrualForMonthlyFrequency($leaveAccrualObject->firstAccrualForMonthlyFrequency, $dateObject, $firstAccrualDateObj, $hireDateObj);
                    }
                } else {

                    $dateObjectEndOftheMonth = $dateObject->copy()->endOfMonth();

                    if ($dateObject->isoFormat('DD') == $dateObjectEndOftheMonth->isoFormat('DD')) {
                        // // first accrual date
                        // $firstAccrualDateObj = $hireDateObj->copy();
                        // return $this->checkFirstAccrualForMonthlyFrequency($leaveAccrualObject->firstAccrualForMonthlyFrequency, $dateObject, $firstAccrualDateObj, $hireDateObj);
                        return true;
                    }
                }

                // if ($hireDateObj->isoFormat('DD') == $currentDay) {
                //     // first accrual date
                //     $firstAccrualDateObj = $hireDateObj->copy();
                //     return $this->checkFirstAccrualForMonthlyFrequency($leaveAccrualObject->firstAccrualForMonthlyFrequency, $dateObject, $firstAccrualDateObj, $hireDateObj);
                // }
                break;
            case 'FIRST_ACCRUE_ON_HIRE_DATE_AND_OTHERS_ON_FIRST_DAY_OF_MONTH':
                var_dump('> FIRST_ACCRUE_ON_HIRE_DATE_AND_OTHERS_ON_FIRST_DAY_OF_MONTH');
                $currentMonth = $dateObject->format('m');
                $currentYear = $dateObject->format('Y');
                $hiredateString = $hireDateObj->format('d');
                $currentmonthHiredate = $currentYear.'-'.$currentMonth.'-'.$hiredateString;
                $currentmonthHireDateObj = Carbon::parse($currentmonthHiredate);
                $firstAccrualDateObj = $hireDateObj->copy();

                if ($dateObject->isSameDay($hireDateObj)) {
                    // first accrual date
                    return $this->checkFirstAccrualForMonthlyFrequency($leaveAccrualObject->firstAccrualForMonthlyFrequency, $dateObject, $firstAccrualDateObj, $hireDateObj);
                } else {
                    $startDateObj = $dateObject->copy()->startOfMonth();
                    if ($dateObject->greaterThan($hireDateObj) && $startDateObj->isoFormat('DD') == $currentDay) {
                        $firstDayOfHiredMonth = $hireDateObj->copy()->startOfMonth();
                        // if ($firstDayOfHiredMonth->isSameDay($hireDateObj)) {
                        //     $firstAccrualDateObj = $hireDateObj;
                        // } else {
                        //     $firstAccrualDateObj = $firstDayOfHiredMonth->copy()->addMonth();
                        // }
                        return $this->checkFirstAccrualForMonthlyFrequency($leaveAccrualObject->firstAccrualForMonthlyFrequency, $dateObject, $firstAccrualDateObj, $hireDateObj);
                    }

                }
                break;
            case 'FIRST_ACCRUE_ON_AFTER_GIVEN_NO_OF_DATES_THEN_MONTHLY_ANIVERSARIES':
                var_dump('> FIRST_ACCRUE_ON_AFTER_GIVEN_NO_OF_DATES_THEN_MONTHLY_ANIVERSARIES');
                $currentMonth = $dateObject->format('m');
                $currentYear = $dateObject->format('Y');
                $dateOfJoinObj = Carbon::parse($dateOfJoin);
                $definedNumOfDays = (!is_null($leaveAccrualObject->firstAccrueAfterNoOfDates)) ? (int) $leaveAccrualObject->firstAccrueAfterNoOfDates : 0;
                $firstAccrueDate = $dateOfJoinObj->copy()->addDays($definedNumOfDays);
                $firstAccrueDateDay = $firstAccrueDate->copy()->isoFormat('DD');
                $aniversaryDayOfCurrentMonth = $currentYear.'-'.$currentMonth.'-'.$firstAccrueDateDay;


                $hiredateString = $hireDateObj->format('d');
                $currentmonthHiredate = $currentYear.'-'.$currentMonth.'-'.$hiredateString;
                $currentmonthHireDateObj = Carbon::parse($currentmonthHiredate);
                $firstAccrualDateObj = $firstAccrueDate->copy();
                
                if ($dateObject->isSameDay($firstAccrueDate)) {
                    // first accrual date
                    return $this->checkFirstAccrualForMonthlyFrequency($leaveAccrualObject->firstAccrualForMonthlyFrequency, $dateObject, $firstAccrualDateObj, $hireDateObj);
                } else {
                    if (Carbon::parse($aniversaryDayOfCurrentMonth)->format('Y-m-d') === $aniversaryDayOfCurrentMonth) {
                        $aniversaryDayOfCurrentMonthObj = Carbon::parse($aniversaryDayOfCurrentMonth);
                        if ($aniversaryDayOfCurrentMonthObj->copy()->isoFormat('DD') == $currentDay) {
                            return $this->checkFirstAccrualForMonthlyFrequency($leaveAccrualObject->firstAccrualForMonthlyFrequency, $dateObject, $firstAccrualDateObj, $hireDateObj);
                        }
                    } else {
    
                        $dateObjectEndOftheMonth = $dateObject->copy()->endOfMonth();
    
                        if ($dateObject->copy()->isoFormat('DD') == $dateObjectEndOftheMonth->isoFormat('DD')) {
                            // // first accrual date
                            // $firstAccrualDateObj = $hireDateObj->copy();
                            // return $this->checkFirstAccrualForMonthlyFrequency($leaveAccrualObject->firstAccrualForMonthlyFrequency, $dateObject, $firstAccrualDateObj, $hireDateObj);
                            return true;
                        }
                    }
                }
                break;
        }
    }

    private function checkFirstAccrualForMonthlyFrequency($firstAccrualForMonthlyFrequency, $dateObject, $firstAccrualDateObject, $hireDateObject)
    {
        switch ($firstAccrualForMonthlyFrequency) {
            case 'FULL_AMOUNT':
                var_dump('> FULL_AMOUNT');
                return true;
                break;
            case 'SKIP':
                var_dump('> SKIP');
                if ($dateObject->isSameDay($firstAccrualDateObject)) {
                    return false;
                } else {
                    return true;
                }
                break;
            case 'FULL_AMOUNT_IF_JOINED_BEFORE_15':
                var_dump('> FULL_AMOUNT_IF_JOINED_BEFORE_15');
                $day = $hireDateObject->isoFormat('D');
                if ($dateObject->isSameDay($firstAccrualDateObject)) {
                    return $day < 15;
                } else {
                    return true;
                }
                break;
        }
    }

    private function checkFirstAccrualForAnnualFrequency($firstAccrualForAnnualFrequency, $dateObject, $firstAccrualDateObject, $hireDateObject, $leaveAccrualObject)
    {
        $leaveAmount = $leaveAccrualObject->amount;

        switch ($firstAccrualForAnnualFrequency) {
            case 'FULL_AMOUNT':
                var_dump('> FULL_AMOUNT');
                return [
                    "status" => true,
                    "leaveCount" => $leaveAmount
                ];
                break;
            case 'SKIP':
                var_dump('> SKIP');
                if ($dateObject->isSameDay($firstAccrualDateObject)) {
                    return [
                        "status" => false,
                        "leaveCount" => null
                    ];
                } else {
                    return [
                        "status" => true,
                        "leaveCount" => $leaveAmount
                    ];
                }
                break;
            case 'FULL_AMOUNT_IF_JOINED_IN_THE_FIRST_HALF_OF_THE_YEAR':
                var_dump('> FULL_AMOUNT_IF_JOINED_IN_THE_FIRST_HALF_OF_THE_YEAR');
                
                $month = $hireDateObject->isoFormat('M');
                if ($dateObject->isSameDay($firstAccrualDateObject)) {
                    // return $month <= 6;
                    if ($month <= 6) {
                        return [
                            "status" => true,
                            "leaveCount" => $leaveAmount
                        ];
                    } else {
                        return [
                            "status" => false,
                            "leaveCount" => null
                        ];
                    }

                } else {
                    return [
                        "status" => true,
                        "leaveCount" => $leaveAmount
                    ];
                }
                break;
            case 'PRO_RATE':
                var_dump('> PRO_RATE');
                if ($dateObject->isSameDay($firstAccrualDateObject) && !empty($leaveAccrualObject->proRateMethodFirstAccrualForAnnualFrequency)) {
                    $month = $hireDateObject->isoFormat('M');

                    $leaveAmount = $this->proRateFormulaRelateLeaveAmount($month, $leaveAccrualObject->proRateMethodFirstAccrualForAnnualFrequency);
                    
                    if (!is_null($leaveAmount)) {
                        return [
                            "status" => true,
                            "leaveCount" => $leaveAmount
                        ];
                    } else {
                        return [
                            "status" => false,
                            "leaveCount" => null
                        ];
                    }

                } else {
                    $leaveAmount = $this->proRateFormulaRelateLeaveAmount(1, $leaveAccrualObject->proRateMethodFirstAccrualForAnnualFrequency);
                    return [
                        "status" => true,
                        "leaveCount" => $leaveAmount
                    ];
                }
                break;
        }
    }

    private function proRateFormulaRelateLeaveAmount($hiredMonth, $proRateFormulaId)
    {
        //get related prorate formula 
        $proRateData = DB::table('proRateFormula')
            ->where('id', '=', $proRateFormulaId)
            ->where('isDelete', false)
            ->first();

        if (is_null($proRateData)) {
            return null;
        }

        $proRateFormulaData = (array) json_decode($proRateData->formulaDetail);
        $leaveAmount = null;

        if ($hiredMonth >= 1 && $hiredMonth <= 3) {
            $leaveAmount = $proRateFormulaData['firstQuater'];
        } elseif ($hiredMonth >= 4 && $hiredMonth <= 6) {
            $leaveAmount = $proRateFormulaData['secondQuater'];
        } elseif ($hiredMonth >= 7 && $hiredMonth <= 9) {
            $leaveAmount = $proRateFormulaData['thirdQuater'];
        } elseif ($hiredMonth >= 10 && $hiredMonth <= 12) {
            $leaveAmount = $proRateFormulaData['forthQuater'];
        } else {
            $leaveAmount = null;
        }
        return $leaveAmount;

    }

    private function getLeaveEntitlement($entitlement)
    {
        return [
            'employeeId' => isset($entitlement['employeeId']) ? $entitlement['employeeId'] : null,
            'leaveTypeId' => isset($entitlement['leaveTypeId']) ? $entitlement['leaveTypeId'] : null,
            'leavePeriodFrom' => isset($entitlement['leavePeriodFrom']) ? $entitlement['leavePeriodFrom'] : null,
            'leavePeriodTo' => isset($entitlement['leavePeriodTo']) ? $entitlement['leavePeriodTo'] : null,
            'type' => 'ACCRUAL',
            'validFrom' => isset($entitlement['validFrom']) ? $entitlement['validFrom'] : null,
            'validTo' => isset($entitlement['validTo']) ? $entitlement['validTo'] : null,
            'entilementCount' => isset($entitlement['entilementCount']) ? $entitlement['entilementCount'] : null,
            'pendingCount' => 0,
            'usedCount' => 0,
            'comment' => isset($entitlement['comment']) ? $entitlement['comment'] : null,
        ];
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
     * Get hire date base leave period for annual accrual
     */
    private function hireDateLeavePeriodForAnnualAccrual($dateObject, $hireDateObject)
    {
        $leavePeriod = [
            'from' => null,
            'to' => null
        ];

        $hiredMonth = $hireDateObject->isoFormat('MM');
        $hiredDay = $hireDateObject->isoFormat('DD');

        $currentYearHireDate = Carbon::now();
        $currentYearHireDate = $this->getRelaventHireDateBaseAccrualDateForGivenYear($currentYearHireDate->format('Y'), $hireDateObject);

        if ($currentYearHireDate->isSameDay($dateObject)) {
            $leavePeriod['from'] = $currentYearHireDate->format('Y-m-d');
            $leavePeriod['to'] = $currentYearHireDate->copy()->addYear()->subDay()->format('Y-m-d');
        } elseif ($currentYearHireDate->greaterThan($dateObject)) { // if today is within previous leave period
            $currentYearHireDateYear = $currentYearHireDate->format('Y');
            $dateObjYear = $dateObject->format('Y');
            $diff = (int)$currentYearHireDateYear - $dateObjYear;

            if ($diff > 0) {
                $leavePeriod['from'] = $dateObject->format('Y-m-d');
                $leavePeriod['to'] = $currentYearHireDate->copy()->subYears($diff)->addYear()->subDay()->format('Y-m-d');
            } else {
                $leavePeriod['from'] = $currentYearHireDate->copy()->subYear()->format('Y-m-d');
                $leavePeriod['to'] = $currentYearHireDate->copy()->subDay()->format('Y-m-d');
            }

        } elseif (!$currentYearHireDate->greaterThan($dateObject)) {
            $currentYearHireDateYear = $currentYearHireDate->format('Y');
            $dateObjYear = $dateObject->format('Y');
            $diff =  (int)$dateObjYear - (int)$currentYearHireDateYear;

            if ($diff > 0) {
                $leavePeriod['from'] = $dateObject->format('Y-m-d');
                $leavePeriod['to'] = $currentYearHireDate->copy()->addYears($diff+1)->subDay()->format('Y-m-d');
            } else {
                $leavePeriod['from'] = $dateObject->format('Y-m-d');
                $leavePeriod['to'] = $dateObject->copy()->addYear()->subDay()->format('Y-m-d');
            }
        } 

        return $leavePeriod;
    }

    private function leaveValidityPeriod($dateObject, $leavePeriodData, $accrualValidFrom)
    {
        if ($accrualValidFrom == 'LEAVE_PERIOD_START_DATE') {
            return [
                'from' => $leavePeriodData['from'],
                'to' => $leavePeriodData['to']
            ];
        }

        return [
            'from' => $dateObject->format('Y-m-d'),
            'to' => $leavePeriodData['to']
        ];
    }

    public function backdatedAccrualProcess($employeeId, $hireDate, $today)
    {
        try {

            // get company details
            $company = DB::table('company')->first(['timeZone', 'leavePeriodStartingMonth', 'leavePeriodEndingMonth']);

            $leaveAccruals = $this->getActiveLeaveAccruals();

            DB::beginTransaction();

            foreach ($leaveAccruals as $leaveAccrual) {
                var_dump($leaveAccrual);

                // get group employees
                $employees = $this->getEmployeeIdsByLeaveGroupId($leaveAccrual->leaveEmployeeGroupId);

                $selectedEmployee = $employees->first(function ($employee) use ($employeeId) {
                    return $employee->id == $employeeId;
                });

                // for MONTHLY allocation & allow to leave accuru
                if ($leaveAccrual->accrualFrequency == "MONTHLY" && !empty($selectedEmployee)) {

                    $creditingFrequency = $leaveAccrual->dayOfCreditingForMonthlyFrequency;

                    $firstAccrualFrequency = $leaveAccrual->firstAccrualForMonthlyFrequency;

                    $dates = $this->getCreditingDates($hireDate, $today, $creditingFrequency, $firstAccrualFrequency, $leaveAccrual);

                    var_dump('cred Dates > ', $dates);

                    if (!empty($dates)) {
                        $leaveAccrualProcess = [
                            'leaveAccrualId' => $leaveAccrual->id,
                            'method' => 'BACKDATED',
                            'numberOfAllocatedEntitlements' => 0
                        ];

                        // create process
                        $processId = DB::table('leaveAccrualProcess')->insertGetId($leaveAccrualProcess);
                    }

                    $leaveAccrualEntitlementLogs = [];

                    foreach ($dates as $date) {
                        // create date onjrct
                        $dateObject = Carbon::createFromFormat('Y-m-d', $date, $company->timeZone);

                        $entitlementId = $this->backdatedLeaveAccrual($selectedEmployee, $dateObject, $leaveAccrual, $company);

                        if (!is_null($entitlementId)) {
                            $leaveAccrualEntitlementLogs[] = [
                                'leaveAccrualProcessId' => $processId,
                                'leaveTypeId' => $leaveAccrual->leaveTypeId,
                                'employeeId' => $employeeId,
                                'leaveEntitlementId' => $entitlementId,
                                'accrualDate' => $dateObject->format('Y-m-d')
                            ];
                        }
                    }

                    if (!empty($leaveAccrualEntitlementLogs)) {
                        // create entitlement logs
                        DB::table('leaveAccrualEntitlementLog')->insert($leaveAccrualEntitlementLogs);

                        DB::table('leaveAccrualProcess')->where('id', $processId)->update(['numberOfAllocatedEntitlements' => count($leaveAccrualEntitlementLogs)]);
                    }
                } elseif ($leaveAccrual->accrualFrequency == "ANNUAL" && !empty($selectedEmployee)) {

                    $creditingFrequency = $leaveAccrual->dayOfCreditingForAnnualFrequency;
                    $leavePeriod = $leaveAccrual->leavePeriod;

                    $firstAccrualFrequency = $leaveAccrual->firstAccrualForAnnualfrequency;

                    $dates = $this->getCreditingDatesForAnnualBackDatedAccrualProcess($hireDate, $today, $creditingFrequency, $firstAccrualFrequency, $leavePeriod);

                    if (!empty($dates)) {
                        $leaveAccrualProcess = [
                            'leaveAccrualId' => $leaveAccrual->id,
                            'method' => 'BACKDATED',
                            'numberOfAllocatedEntitlements' => 0
                        ];

                        // create process
                        $processId = DB::table('leaveAccrualProcess')->insertGetId($leaveAccrualProcess);
                    }

                    $leaveAccrualEntitlementLogs = [];

                    foreach ($dates as $date) {
                        // create date onjrct
                        $dateObject = Carbon::createFromFormat('Y-m-d', $date, $company->timeZone);

                        $entitlementId = $this->backdatedAnnualLeaveAccrual($selectedEmployee, $dateObject, $leaveAccrual, $company);

                        if (!is_null($entitlementId)) {
                            $leaveAccrualEntitlementLogs[] = [
                                'leaveAccrualProcessId' => $processId,
                                'leaveTypeId' => $leaveAccrual->leaveTypeId,
                                'employeeId' => $employeeId,
                                'leaveEntitlementId' => $entitlementId,
                                'accrualDate' => $dateObject->format('Y-m-d')
                            ];
                        }
                    }

                    if (!empty($leaveAccrualEntitlementLogs)) {
                        // create entitlement logs
                        DB::table('leaveAccrualEntitlementLog')->insert($leaveAccrualEntitlementLogs);

                        DB::table('leaveAccrualProcess')->where('id', $processId)->update(['numberOfAllocatedEntitlements' => count($leaveAccrualEntitlementLogs)]);
                    }
                }
            }

            DB::commit();

            return $this->jobResponse(false);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->jobResponse(true, $e->getMessage());
        }
    }

    private function getCreditingDates($hireDate, $endDate, $creditingFrequency, $firstAccrualFrequency, $leaveAccrualObj)
    {
        $dates = [];

        switch ($creditingFrequency) {
            case 'MONTHLY_HIRE_DATE':
                $dates = $this->getHireAnniversaries($hireDate, $endDate, $firstAccrualFrequency);
                break;
            case 'FIRST_ACCRUE_ON_HIRE_DATE_AND_OTHERS_ON_FIRST_DAY_OF_MONTH':
                $dates = $this->getRelatedAccrualDates($hireDate, $endDate, $firstAccrualFrequency);
                break;
            case 'FIRST_DAY':
                $dates = $this->getStartingDays($hireDate, $endDate, $firstAccrualFrequency);
                break;
            case 'LAST_DAY':
                $dates = $this->getEndingDays($hireDate, $endDate, $firstAccrualFrequency);
                break;
            case 'FIRST_ACCRUE_ON_AFTER_GIVEN_NO_OF_DATES_THEN_MONTHLY_ANIVERSARIES':
                $dates = $this->getRelatedAccrualDatesForPredefineFirstAcrrueFrequency($hireDate, $endDate, $firstAccrualFrequency, $leaveAccrualObj);
                break;
        }

        return $dates;
    }

    private function getCreditingDatesForAnnualBackDatedAccrualProcess($hireDate, $endDate, $creditingFrequency, $firstAccrualFrequency, $leavePeriod)
    {
        $dates = [];

        switch ($leavePeriod) {
            case 'STANDARD':
                $dates = $this->getStandardBaseAnualAccrualDates($hireDate, $endDate, $firstAccrualFrequency, $creditingFrequency);
                break;
            case 'HIRE_DATE_BASED':
                $dates = $this->getHireDateBaseAnualAccrualDates($hireDate, $endDate, $firstAccrualFrequency);
                break;
        }

        return $dates;
    }

    private function getHireDateBaseAnualAccrualDates($hireDate, $endDate, $firstAccrualFrequency)
    {
        $endDateObj = Carbon::createFromFormat('Y-m-d', $endDate);
        $hireDateObj = Carbon::parse($hireDate);
        $hireDay = $hireDateObj->format('d');

        $currentYearMonthObj = Carbon::createFromFormat('Y-m', $hireDateObj->format('Y-m'));
        $currentYearAnniversary = $hireDateObj->copy();
        $dates = [];

        $numberOfDays = 1;

        while ($currentYearAnniversary->lessThanOrEqualTo($endDateObj)) {

            if ($numberOfDays == 1) {
                if ($this->checkAnnualAccrualFirstDateRule($firstAccrualFrequency, $hireDateObj)) {
                    $dates[] = $currentYearAnniversary->format('Y-m-d');
                }
            } else {
                $dates[] = $currentYearAnniversary->format('Y-m-d');
            }

            $currentYearMonthStartDay = $currentYearMonthObj->startOfMonth()->addYear();
            $daysInMonth = $currentYearMonthStartDay->daysInMonth;
            $days = ($daysInMonth < $hireDay) ? $daysInMonth : $hireDay;
            $currentYearAnniversary = $currentYearMonthStartDay->copy()->addDays($days - 1);
            $numberOfDays++;
        }

        $filteredDatesArr = [];
        if (!empty($dates)) {
            $lengthOfDatesArray = sizeof($dates);
            $recentAccrualDateForCurrentDate = $dates[$lengthOfDatesArray-1];
            $filteredDatesArr[] =  $recentAccrualDateForCurrentDate;

        }

        return $filteredDatesArr;
    }


    private function getHireAnniversaries($hireDate, $endDate, $firstAccrualFrequency)
    {
        $endDateObj = Carbon::createFromFormat('Y-m-d', $endDate);
        $hireDateObj = Carbon::parse($hireDate);
        $hireDay = $hireDateObj->format('d');

        $currentYearMonthObj = Carbon::createFromFormat('Y-m', $hireDateObj->format('Y-m'));
        $currentMonthAnniversary = $hireDateObj->copy();
        $dates = [];

        $numberOfDays = 1;

        while ($currentMonthAnniversary->lessThanOrEqualTo($endDateObj)) {

            if ($numberOfDays == 1) {
                if ($this->checkFirstDateRule($firstAccrualFrequency, $hireDateObj)) {
                    $dates[] = $currentMonthAnniversary->format('Y-m-d');
                }
            } else {
                $dates[] = $currentMonthAnniversary->format('Y-m-d');
            }

            $currentYearMonthStartDay = $currentYearMonthObj->startOfMonth()->addMonth();
            $daysInMonth = $currentYearMonthStartDay->daysInMonth;
            $days = ($daysInMonth < $hireDay) ? $daysInMonth : $hireDay;
            $currentMonthAnniversary = $currentYearMonthStartDay->copy()->addDays($days - 1);
            $numberOfDays++;
        }

        return $dates;
    }

    private function getStartingDays($hireDate, $endDate, $firstAccrualFrequency)
    {
        $endDateObj = Carbon::createFromFormat('Y-m-d', $endDate);
        $hireDateObj = Carbon::parse($hireDate);

        $currentYearMonthObj = Carbon::createFromFormat('Y-m', $hireDateObj->format('Y-m'));
        $currentYearMonthStartDay = $currentYearMonthObj->startOfMonth();
        $dates = [];

        $numberOfDays = 1;

        while ($currentYearMonthStartDay->lessThanOrEqualTo($endDateObj)) {

            if ($numberOfDays == 1) {
                if ($this->checkFirstDateRule($firstAccrualFrequency, $hireDateObj)) {
                    $dates[] = $currentYearMonthStartDay->format('Y-m-d');
                }
            } else {
                $dates[] = $currentYearMonthStartDay->format('Y-m-d');
            }

            $currentYearMonthStartDay = $currentYearMonthObj->addMonth()->startOfMonth();
            $numberOfDays++;
        }

        return $dates;
    }


    private function getRelatedAccrualDates($hireDate, $endDate, $firstAccrualFrequency)
    {
        $endDateObj = Carbon::createFromFormat('Y-m-d', $endDate);
        $hireDateObj = Carbon::parse($hireDate);

        $currentYearMonthObj = Carbon::createFromFormat('Y-m', $hireDateObj->format('Y-m'));
        $currentYearMonthStartDay = $currentYearMonthObj->addMonth()->startOfMonth();
        $dates = [];

        

        if ($this->checkFirstDateRule($firstAccrualFrequency, $hireDateObj)) {
            $dates [] = $hireDate;
        }

        $numberOfDays = 2;
        while ($currentYearMonthStartDay->lessThanOrEqualTo($endDateObj)) {

            $dates[] = $currentYearMonthStartDay->format('Y-m-d');

            $currentYearMonthStartDay = $currentYearMonthObj->addMonth()->startOfMonth();
            $numberOfDays++;
        }

        return $dates;
    }

    private function getRelatedAccrualDatesForPredefineFirstAcrrueFrequency($hireDate, $endDate, $firstAccrualFrequency, $leaveAccrualObj)
    {
        $endDateObj = Carbon::createFromFormat('Y-m-d', $endDate);
        $dateOfJoinObj = Carbon::parse($hireDate);
        $predifieneNuOfDates= (!is_null($leaveAccrualObj->firstAccrueAfterNoOfDates)) ? $leaveAccrualObj->firstAccrueAfterNoOfDates : 0;
        $firstAccrueDateObj = $dateOfJoinObj->copy()->addDays($predifieneNuOfDates);
        // $hireDateObj = Carbon::parse($hireDate);
        $hireDateObj = $firstAccrueDateObj->copy();
        $hireDay = $hireDateObj->format('d');

        $currentYearMonthObj = Carbon::createFromFormat('Y-m', $hireDateObj->format('Y-m'));
        $currentMonthAnniversary = $hireDateObj->copy();
        $dates = [];

        $numberOfDays = 1;

        while ($currentMonthAnniversary->lessThanOrEqualTo($endDateObj)) {

            if ($numberOfDays == 1) {
                if ($this->checkFirstDateRule($firstAccrualFrequency, $hireDateObj)) {
                    $dates[] = $currentMonthAnniversary->format('Y-m-d');
                }
            } else {
                $dates[] = $currentMonthAnniversary->format('Y-m-d');
            }

            $currentYearMonthStartDay = $currentYearMonthObj->startOfMonth()->addMonth();
            $daysInMonth = $currentYearMonthStartDay->daysInMonth;
            $days = ($daysInMonth < $hireDay) ? $daysInMonth : $hireDay;
            $currentMonthAnniversary = $currentYearMonthStartDay->copy()->addDays($days - 1);
            $numberOfDays++;
        }

        return $dates;
    }


    private function getStandardBaseAnualAccrualDates($hireDate, $endDate, $firstAccrualFrequency, $creditingFrequency)
    {
        $endDateObj = Carbon::createFromFormat('Y-m-d', $endDate);
        $hireDateObj = Carbon::parse($hireDate);
        $hiredYear = $hireDateObj->format('Y');
        $hiredYearAccrualDay = $hiredYear.'-'.$creditingFrequency;
        $hiredYearAccrualDayObj = Carbon::createFromFormat('Y-m-d', $hiredYearAccrualDay);

        // $currentYearMonthObj = Carbon::createFromFormat('Y-m', $hireDateObj->format('Y-m'));
        $currentYearAcrualDay = $hiredYearAccrualDayObj->copy();
        $dates = [];

        $numberOfDays = 1;

        while ($currentYearAcrualDay->lessThanOrEqualTo($endDateObj)) {

            if ($numberOfDays == 1) {
                if ($this->checkAnnualAccrualFirstDateRule($firstAccrualFrequency, $hireDateObj)) {
                    $dates[] = $currentYearAcrualDay->format('Y-m-d');
                }
            } else {
                $dates[] = $currentYearAcrualDay->format('Y-m-d');
            }

            $currentYearAcrualDay = $currentYearAcrualDay->addYear();
            $numberOfDays++;
        }

        $filteredDatesArr = [];
        if (!empty($dates)) {
            $lengthOfDatesArray = sizeof($dates);
            $recentAccrualDateForCurrentDate = $dates[$lengthOfDatesArray-1];
            $filteredDatesArr[] =  $recentAccrualDateForCurrentDate;

        }

        return $filteredDatesArr;
    }

    private function getEndingDays($hireDate, $endDate, $firstAccrualFrequency)
    {
        $endDateObj = Carbon::createFromFormat('Y-m-d', $endDate);
        $hireDateObj = Carbon::parse($hireDate);

        $currentYearMonthObj = Carbon::createFromFormat('Y-m', $hireDateObj->format('Y-m'));
        $currentYearMonthEndDay = $currentYearMonthObj->endOfMonth();
        $dates = [];

        $numberOfDays = 1;

        while ($currentYearMonthEndDay->lessThanOrEqualTo($endDateObj)) {

            if ($numberOfDays == 1) {
                if ($this->checkFirstDateRule($firstAccrualFrequency, $hireDateObj)) {
                    $dates[] = $currentYearMonthEndDay->format('Y-m-d');
                }
            } else {
                $dates[] = $currentYearMonthEndDay->format('Y-m-d');
            }

            $startOfMonth = $currentYearMonthEndDay->copy()->startOfMonth();
            $currentYearMonthEndDay =  $startOfMonth->addMonth()->endOfMonth();
            $numberOfDays++;
        }

        return $dates;
    }

    private function checkFirstDateRule($firstAccrualFrequency, $hireDateObject)
    {
        switch ($firstAccrualFrequency) {
            case 'FULL_AMOUNT':
                return true;
            case 'SKIP':
                return false;
            case 'FULL_AMOUNT_IF_JOINED_BEFORE_15':
                return ($hireDateObject->isoFormat('D') < 15);
        }
    }

    private function checkAnnualAccrualFirstDateRule($firstAccrualFrequency, $hireDateObject)
    {
        switch ($firstAccrualFrequency) {
            case 'FULL_AMOUNT':
                return true;
            case 'SKIP':
                return false;
            case 'FULL_AMOUNT_IF_JOINED_IN_THE_FIRST_HALF_OF_THE_YEAR':                
                $month = $hireDateObject->isoFormat('M');
                return $month <= 6;
                break;
            case 'PRO_RATE':
                var_dump('> PRO_RATE');
                return true;
                //TO DO
                break;
        }
    }

    private function backdatedAnnualLeaveAccrual($employee, $dateObject, $leaveAccrualObject, $company)
    {
        $actualHireDate = !empty($employee->recentHireDate) ? $employee->recentHireDate : $employee->hireDate;
        $hireDateObj = Carbon::createFromFormat('Y-m-d', $actualHireDate, $company->timeZone);
        // ignore feautre hire dated employees
        // if ($hireDateObj->greaterThan($dateObject)) {
        //     return null;
        // }

        if ($hireDateObj->copy()->startOfDay()->greaterThan($dateObject->copy()->startOfDay())) {
            return null;
        }

        // get employee current job
        $employeeJob = $this->getEmployeeJob($employee->id, $dateObject->format('Y-m-d'), ['locationId']);

        // check whether employee has a job, if job not exist ignore that employee
        if (empty($employeeJob)) {
            return null;
        }

        // check accrual exist
        $accruedEmployee = DB::table('leaveAccrualEntitlementLog')
            ->where('employeeId', '=', $employee->id)
            ->where('leaveTypeId', '=', $leaveAccrualObject->leaveTypeId)
            ->whereDate('accrualDate', '=', $dateObject->format('Y-m-d'))
            ->first('id');

        if (!is_null($accruedEmployee)) {
            return null;
        }

        $employeeEntitlement = $this->getAccrualEntitlementsForAnnualAccrual($dateObject, $employee, $leaveAccrualObject, $company);

        $entitlementId = null;

        if (!empty($employeeEntitlement)) {
            // add entitlement
            $entitlementId = DB::table('leaveEntitlement')->insertGetId($employeeEntitlement);
        }

        return $entitlementId;
    }


    private function backdatedLeaveAccrual($employee, $dateObject, $leaveAccrualObject, $company)
    {
        $actualHireDate = !empty($employee->recentHireDate) ? $employee->recentHireDate : $employee->hireDate;
        $hireDateObj = Carbon::createFromFormat('Y-m-d', $actualHireDate, $company->timeZone);
        // ignore feautre hire dated employees
        // if ($hireDateObj->greaterThan($dateObject)) {
        //     return null;
        // }
        if ($hireDateObj->copy()->startOfDay()->greaterThan($dateObject->copy()->startOfDay())) {
            return null;
        }

        // get employee current job
        $employeeJob = $this->getEmployeeJob($employee->id, $dateObject->format('Y-m-d'), ['locationId', 'employmentStatusId', 'effectiveDate']);

        // check whether employee has a job, if job not exist ignore that employee
        if (empty($employeeJob)) {
            return null;
        }

        //get employementStatus
        $employmentStatusId = empty($employeeJob->employmentStatusId) ? null : $employeeJob->employmentStatusId;

        // ignore employee if location not exist
        if (empty($employmentStatusId)) {
            return null;
        }

        //get employementStatus
        $employmentStatusDetail = DB::table('employmentStatus')->where('id', $employmentStatusId)->first();

        if ($employmentStatusDetail->category == 'CONTRACT') {
            $hireDateObj = Carbon::createFromFormat('Y-m-d', $employeeJob->effectiveDate);
        } 

        //check whether accrual is allocated only for joined year
        if ($leaveAccrualObject->isAllocatedOnlyForJoinedYear) {
            $hireDateYearEndObj = $hireDateObj->copy()->endOfYear();

            if ($dateObject->copy()->startOfDay()->greaterThan($hireDateYearEndObj->startOfDay())) {
                return null;
            }
        }

        if ($leaveAccrualObject->leavePeriod == "STANDARD") {
            if ($employmentStatusDetail->category == 'PROBATION' || $employmentStatusDetail->category == 'CONTRACT') {
    
                if (!empty($employmentStatusDetail->period) && !empty($employmentStatusDetail->periodUnit)) {
    
                    $empStatusEffectiveFromObj = Carbon::parse($employeeJob->effectiveDate);
                    $empStatusEffectiveToObj = null;
    
                    switch ($employmentStatusDetail->periodUnit) {
                        case 'YEARS':
                            $empStatusEffectiveToObj = $empStatusEffectiveFromObj->copy()->addYears($employmentStatusDetail->period);
                            break;
                        case 'MONTHS':
                            $empStatusEffectiveToObj = $empStatusEffectiveFromObj->copy()->addMonths($employmentStatusDetail->period);
                            break;
                        case 'DAYS':
                            $empStatusEffectiveToObj = $empStatusEffectiveFromObj->copy()->addDays($employmentStatusDetail->period);
                            break;
                        default:
                            # code...
                            break;
                    }
                    
                    //check date object is within the employeeStatusEffectiveRange
                    // if ($leaveAccrualObject->dayOfCreditingForMonthlyFrequency === 'MONTHLY_HIRE_DATE') {
                    //     if (!($dateObject->copy()->startOfDay()->greaterThanOrEqualTo($empStatusEffectiveFromObj->copy()->startOfDay()) && $dateObject->copy()->startOfDay()->lessThan($empStatusEffectiveToObj->copy()->startOfDay()))) {
                    //         return null;
                    //     }
                    // } else {
                    //     if (!($dateObject->copy()->startOfDay()->greaterThan($empStatusEffectiveFromObj->copy()->startOfDay()) && $dateObject->copy()->startOfDay()->lessThan($empStatusEffectiveToObj->copy()->startOfDay()))) {
                    //         return null;
                    //     }
                    // }
                    // //check date object is within the employeeStatusEffectiveRange
                    if (!$dateObject->between($empStatusEffectiveFromObj,$empStatusEffectiveToObj)) {
                        return null;
                    }

                    
                    
                }
            }
        } else if ($leaveAccrualObject->leavePeriod == "HIRE_DATE_BASED") {

            if ($employmentStatusDetail->category == 'PROBATION' || $employmentStatusDetail->category == 'CONTRACT') {
                if (!empty($employmentStatusDetail->periodUnit) && !empty($employmentStatusDetail->period)) {
                    $leavePeriod['from'] = $hireDateObj->copy()->format('Y-m-d');
                    $leavePeriod['to'] = null;
                    $fromDateObj = $hireDateObj->copy();
                    $toDateObj = null;

                    $hiredMonth = $hireDateObj->copy()->isoFormat('MM');
                    $hiredDay = $hireDateObj->copy()->isoFormat('DD');

                    switch ($employmentStatusDetail->periodUnit) {
                        case 'YEARS':
                            $toDateObj = $fromDateObj->copy()->addYears($employmentStatusDetail->period)->subDay();
                            break;
                        case 'MONTHS':
                            $toDateObj = $fromDateObj->copy()->addMonths($employmentStatusDetail->period)->subDay();
                            break;
                        case 'DAYS':
                            $toDateObj = $fromDateObj->copy()->addDays($employmentStatusDetail->period)->subDay();
                            break;
                        default:
                            # code...
                            break;
                    }

                    //check date object is within the employeeStatusEffectiveRange
                    if (!$dateObject->between($fromDateObj,$toDateObj)) {
                        return null;
                    }

                    // if (!($dateObject->copy()->startOfDay()->greaterThan($fromDateObj->copy()->startOfDay()) && $dateObject->copy()->startOfDay()->lessThan($toDateObj->copy()->startOfDay()))) {
                    //     return null;
                    // }

                } 
            }

        }


        // check accrual exist
        $accruedEmployee = DB::table('leaveAccrualEntitlementLog')
            ->where('employeeId', '=', $employee->id)
            ->where('leaveTypeId', '=', $leaveAccrualObject->leaveTypeId)
            ->whereDate('accrualDate', '=', $dateObject->format('Y-m-d'))
            ->first('id');

        if (!is_null($accruedEmployee)) {
            return null;
        }

        $employeeEntitlement = $this->getAccrualEntitlements($dateObject, $employee, $leaveAccrualObject, $company, $employmentStatusDetail, $employeeJob);

        $entitlementId = null;

        if (!empty($employeeEntitlement)) {
            // add entitlement
            $entitlementId = DB::table('leaveEntitlement')->insertGetId($employeeEntitlement);
        }

        return $entitlementId;
    }

    /**
     * Get active leave accruals
     */
    private function getActiveLeaveAccruals()
    {
        return DB::table('leaveAccrual')
            ->join('leaveType', 'leaveType.id', '=', 'leaveAccrual.leaveTypeId')
            ->where('leaveType.isDelete', '=', 0)
            ->get(['leaveAccrual.*', 'leaveType.leavePeriod']);
    }
}
