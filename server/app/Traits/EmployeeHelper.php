<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Employee related helper functions
 */
trait EmployeeHelper
{
    /**
     * Get employee job aganst given date
     * 
     * @param  int $employeeId
     * @param  string $date date which is compare with effective date (Y-m-d format)
     * @param  array $fields selected fields
     * 
     * @return object | null | \Throwable
     */
    protected function getEmployeeJob($employeeId, $date, $fields = ['*'])
    {
        try {
            return DB::table('employeeJob')->where('employeeId', $employeeId)->where('effectiveDate', '<=', $date)
                ->orderBy('effectiveDate', 'desc')
                ->orderBy('updatedAt', 'desc')
                ->first($fields);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Get calendar day type id 
     * @param  int $calendarId Calendar Id
     * @param  string dayName A full textual representation of the day of the week / Sunday through Saturday / format('l') /
     * @return int | null
     */
    protected function getCalendarDayTypeId($calendarId, $dayName)
    {
        $calendar = DB::table('workCalendar')
            ->join('workCalendarDateNames', 'workCalendar.id', '=', 'workCalendarDateNames.calendarId')
            ->leftJoin('dayOfWeek', 'workCalendarDateNames.dayOfWeekId', '=', 'dayOfWeek.id')
            ->where('workCalendar.id', '=', $calendarId)
            ->where('dayOfWeek.dayName', '=', strtolower($dayName))
            ->first(['workCalendarDateNames.workCalendarDayTypeId']);

        if (empty($calendar)) {
            return null;
        }
        return $calendar->workCalendarDayTypeId;
    }

    /**
     * Get Employee workshift  
     * @param int $employeeId Employee Id
     * @param Date $dateObject 
     * @param int $calendarDayTypeId
     *
     * @return null | Object
     */
    protected function getEmployeeWorkShift($employeeId, $dateObject, $calendarDayTypeId = null)
    {
        $employeeJob = null;

        if (is_null($calendarDayTypeId)) {
            // get employee current job
            $employeeJob = $this->getEmployeeJob($employeeId, $dateObject->format('Y-m-d'), ['calendarId', 'locationId']);
            // check whether employee has a job, if job not exist ignore that employee
            if (empty($employeeJob)) {
                return null;
            }
            // get day name
            $dayName = $dateObject->format('l');
            // to check whether holiday 
            $holiday = DB::table('workCalendarSpecialDays')->where('calendarId', $employeeJob->calendarId)->where('date', $dateObject->format('Y-m-d'))->where('workCalendarDayTypeId', '!=', 1)->first(['calendarId', 'workCalendarDayTypeId']);

            $calendarDayTypeId = null;
            if (empty($holiday)) {
                $calendarDayTypeId = $this->getCalendarDayTypeId($employeeJob->calendarId, $dayName);
            } else { // if holiday
                $calendarDayTypeId = $holiday->workCalendarDayTypeId;
            }
        }

        // check whether emplyee has adhod workshift for this date
        $adhocWorkShift = DB::table('adhocWorkshifts')
            ->join('workShifts', 'workShifts.id', '=', 'adhocWorkshifts.workShiftId')
            ->join('workShiftDayType', 'workShiftDayType.workShiftId', '=', 'workShifts.id')
            ->where('adhocWorkshifts.employeeId', $employeeId)
            ->where('adhocWorkshifts.date', $dateObject->format('Y-m-d'))
            ->where('workShiftDayType.dayTypeId', $calendarDayTypeId)
            ->first([
                'workShifts.id',
                'workShifts.name as workShiftName',
                'workShifts.shiftType',
                'workShiftDayType.dayTypeId',
                'workShiftDayType.noOfDay',
                'workShiftDayType.startTime',
                'workShiftDayType.endTime',
                'workShiftDayType.workHours',
                'workShiftDayType.breakTime',
                'workShiftDayType.hasMidnightCrossOver',
                'workShiftDayType.halfDayLength',
                'workShiftDayType.gracePeriod',
                'workShiftDayType.isOTEnabled',
                'workShiftDayType.isBehaveAsNonWorkingDay',
                'workShiftDayType.inOvertime',
                'workShiftDayType.outOvertime',
                'workShiftDayType.deductLateFromOvertime',
                'workShiftDayType.minimumOT',
                'workShiftDayType.roundOffMethod',
                'workShiftDayType.roundOffToNearest'
            ]);

        if (!empty($adhocWorkShift)) {
            return $adhocWorkShift;
        }

        // check employee shift
        $employeeShift = DB::table('employeeShift')
            ->join('workShifts', 'workShifts.id', '=', 'employeeShift.workShiftId')
            ->join('workShiftDayType', 'workShiftDayType.workShiftId', '=', 'workShifts.id')
            ->where('employeeShift.employeeId', $employeeId)
            ->where('employeeShift.effectiveDate', '<=', $dateObject->format('Y-m-d'))
            ->where('workShiftDayType.dayTypeId', $calendarDayTypeId)
        ->first([
            'workShifts.id',
            'workShifts.name as workShiftName',
            'workShifts.shiftType',
            'workShiftDayType.dayTypeId',
            'workShiftDayType.noOfDay',
            'workShiftDayType.startTime',
            'workShiftDayType.endTime',
            'workShiftDayType.workHours',
            'workShiftDayType.breakTime',
            'workShiftDayType.hasMidnightCrossOver',
            'workShiftDayType.halfDayLength',
            'workShiftDayType.gracePeriod',
            'workShiftDayType.isOTEnabled',
            'workShiftDayType.isBehaveAsNonWorkingDay',
            'workShiftDayType.inOvertime',
            'workShiftDayType.outOvertime',
            'workShiftDayType.deductLateFromOvertime',
            'workShiftDayType.minimumOT',
            'workShiftDayType.roundOffMethod',
            'workShiftDayType.roundOffToNearest'
        ]);

        if (!empty($employeeShift)) {
            return $employeeShift;
        }

        // get date name id
        $dayNameId = $dateObject->format('w');

        // check employee work schedule shift
        $employeeWorkPatternShift = DB::table('employeeWorkPattern')
            ->join('workPatternWeek', 'workPatternWeek.workPatternId', '=', 'employeeWorkPattern.workPatternId')
            ->join('workPatternWeekDay', 'workPatternWeekDay.workPatternWeekId', '=', 'workPatternWeek.id')
            ->join('workShifts', 'workShifts.id', '=', 'workPatternWeekDay.workShiftId')
            ->join('workShiftDayType', 'workShiftDayType.workShiftId', '=', 'workShifts.id')
            ->where('employeeWorkPattern.employeeId', $employeeId)
            ->where('employeeWorkPattern.effectiveDate', '<=', $dateObject->format('Y-m-d'))
            ->where('workPatternWeekDay.dayTypeId', $dayNameId)
            ->where('workShiftDayType.dayTypeId', $calendarDayTypeId)
            ->orderBy('employeeWorkPattern.effectiveDate', 'desc')
            ->orderBy('employeeWorkPattern.updatedAt', 'desc')
            ->first([
                'workShifts.id',
                'workShifts.name as workShiftName',
                'workShifts.shiftType',
                'workShiftDayType.dayTypeId',
                'workPatternWeek.workPatternId',
                'workShiftDayType.noOfDay',
                'workShiftDayType.startTime',
                'workShiftDayType.endTime',
                'workShiftDayType.workHours',
                'workShiftDayType.breakTime',
                'workShiftDayType.hasMidnightCrossOver',
                'workShiftDayType.halfDayLength',
                'workShiftDayType.gracePeriod',
                'workShiftDayType.isOTEnabled',
                'workShiftDayType.isBehaveAsNonWorkingDay',
                'workShiftDayType.inOvertime',
                'workShiftDayType.outOvertime',
                'workShiftDayType.deductLateFromOvertime',
                'workShiftDayType.minimumOT',
                'workShiftDayType.roundOffMethod',
                'workShiftDayType.roundOffToNearest'
            ]);


        // return if employee relate work pattern exist
        if (!empty($employeeWorkPatternShift)) {
            return $employeeWorkPatternShift;
        }

        if (is_null($employeeJob)) {
            $employeeJob = $this->getEmployeeJob($employeeId, $dateObject->format('Y-m-d'), ['calendarId', 'locationId']);
            // check whether employee has a job, if job not exist ignore that employee
            if (empty($employeeJob)) {
                return null;
            }
        }

        // check location shift
        // $locationWorkPatternShift = DB::table('workPatternLocation')
        //     ->join('workPatternWeek', 'workPatternWeek.workPatternId', '=', 'workPatternLocation.workPatternId')
        //     ->join('workPatternWeekDay', 'workPatternWeekDay.workPatternWeekId', '=', 'workPatternWeek.id')
        //     ->join('workShifts', 'workShifts.id', '=', 'workPatternWeekDay.workShiftId')
        //     ->join('workShiftDayType', 'workShiftDayType.workShiftId', '=', 'workShifts.id')
        //     ->where('workPatternLocation.locationId', $employeeJob->locationId)
        //     ->where('workPatternWeekDay.dayTypeId', $dayNameId)
        //     ->where('workShiftDayType.dayTypeId', $calendarDayTypeId)
        //     ->first([
        //         'workShifts.id',
        //         'workShifts.name as workShiftName',
        //         'workShifts.shiftType',
        //         'workShiftDayType.dayTypeId',
        //         'workShiftDayType.noOfDay',
        //         'workShiftDayType.startTime',
        //         'workShiftDayType.endTime',
        //         'workShiftDayType.workHours',
        //         'workShiftDayType.breakTime',
        //         'workShiftDayType.hasMidnightCrossOver',
        //         'workShiftDayType.halfDayLength',
        //         'workShiftDayType.gracePeriod',
        //         'workShiftDayType.isOTEnabled',
        //         'workShiftDayType.isBehaveAsNonWorkingDay',
        //         'workShiftDayType.inOvertime',
        //         'workShiftDayType.outOvertime',
        //         'workShiftDayType.deductLateFromOvertime',
        //         'workShiftDayType.minimumOT',
        //         'workShiftDayType.roundOffMethod',
        //         'workShiftDayType.roundOffToNearest'
        //     ]);

        // if (!empty($locationWorkPatternShift)) {
        //     return $locationWorkPatternShift;
        // }

        return null;
    }

    /**
     * Get Employee workshift  
     * @param int $employeeId Employee Id
     * @param Date $dateObject 
     * @param int $calendarId
     *
     * @return null | Object
     */
    protected function getEmployeeWorkShiftByCalendarId($employeeId, $dateObject, $calendarId)
    {
        // get day name
        $dayName = $dateObject->format('l');
        // format date
        $date = $dateObject->format('Y-m-d');

        // to check whether holiday 
        $holiday = DB::table('workCalendarSpecialDays')->where('calendarId', $calendarId)->where('date', $date)->where('workCalendarDayTypeId', '!=', 1)->first(['calendarId', 'workCalendarDayTypeId']);

        $calendarDayTypeId = null;
        if (empty($holiday)) {
            $calendarDayTypeId = $this->getCalendarDayTypeId($calendarId, $dayName);
        } else { // if holiday
            $calendarDayTypeId = $holiday->workCalendarDayTypeId;
        }

        // check whether emplyee has adhod workshift for this date
        $adhocWorkShift = DB::table('adhocWorkshifts')
        ->join('workShifts', 'workShifts.id', '=', 'adhocWorkshifts.workShiftId')
        ->join('workShiftDayType', 'workShiftDayType.workShiftId', '=', 'workShifts.id')
        ->where('adhocWorkshifts.employeeId', $employeeId)
            ->where('adhocWorkshifts.date', $date)
            ->where('workShiftDayType.dayTypeId', $calendarDayTypeId)
            ->first([
                'workShifts.id',
                'workShifts.shiftType',
                'workShiftDayType.dayTypeId',
                'workShiftDayType.noOfDay',
                'workShiftDayType.startTime',
                'workShiftDayType.endTime',
                'workShiftDayType.workHours',
                'workShiftDayType.breakTime',
                'workShiftDayType.hasMidnightCrossOver',
                'workShiftDayType.halfDayLength',
                'workShiftDayType.gracePeriod',
                'workShiftDayType.isOTEnabled',
                'workShiftDayType.isBehaveAsNonWorkingDay',
                'workShiftDayType.inOvertime',
                'workShiftDayType.outOvertime',
                'workShiftDayType.deductLateFromOvertime',
                'workShiftDayType.minimumOT',
                'workShiftDayType.roundOffMethod',
                'workShiftDayType.roundOffToNearest'
            ]);

        if (!empty($adhocWorkShift)) {
            return $adhocWorkShift;
        }

        // check employee shift
        $employeeShift = DB::table('employeeShift')
        ->join('workShifts', 'workShifts.id', '=', 'employeeShift.workShiftId')
        ->join('workShiftDayType', 'workShiftDayType.workShiftId', '=', 'workShifts.id')
        ->where('employeeShift.employeeId', $employeeId)
            ->where('employeeShift.effectiveDate', '<=', $date)
            ->where('workShiftDayType.dayTypeId', $calendarDayTypeId)
            ->first([
                'workShifts.id',
                'workShifts.shiftType',
                'workShiftDayType.dayTypeId',
                'workShiftDayType.noOfDay',
                'workShiftDayType.startTime',
                'workShiftDayType.endTime',
                'workShiftDayType.workHours',
                'workShiftDayType.breakTime',
                'workShiftDayType.hasMidnightCrossOver',
                'workShiftDayType.halfDayLength',
                'workShiftDayType.gracePeriod',
                'workShiftDayType.isOTEnabled',
                'workShiftDayType.isBehaveAsNonWorkingDay',
                'workShiftDayType.inOvertime',
                'workShiftDayType.outOvertime',
                'workShiftDayType.deductLateFromOvertime',
                'workShiftDayType.minimumOT',
                'workShiftDayType.roundOffMethod',
                'workShiftDayType.roundOffToNearest'
            ]);

        if (!empty($employeeShift)) {
            return $employeeShift;
        }

        // get date name id
        $dayNameId = $dateObject->format('w');

        // check employee work schedule shift
        $employeeWorkPatternShift = DB::table('employeeWorkPattern')
        ->join('workPatternWeek', 'workPatternWeek.workPatternId', '=', 'employeeWorkPattern.workPatternId')
        ->join('workPatternWeekDay', 'workPatternWeekDay.workPatternWeekId', '=', 'workPatternWeek.id')
        ->join('workShifts', 'workShifts.id', '=', 'workPatternWeekDay.workShiftId')
        ->join('workShiftDayType', 'workShiftDayType.workShiftId', '=', 'workShifts.id')
        ->where('employeeWorkPattern.employeeId', $employeeId)
        ->where('employeeWorkPattern.effectiveDate', '<=', $dateObject->format('Y-m-d'))
        ->where('workPatternWeekDay.dayTypeId', $dayNameId)
        ->where('workShiftDayType.dayTypeId', $calendarDayTypeId)
        ->orderBy('employeeWorkPattern.effectiveDate', 'desc')
        ->orderBy('employeeWorkPattern.updatedAt', 'desc')
        ->first([
            'workShifts.id',
            'workShifts.shiftType',
            'workShiftDayType.dayTypeId',
            'workShiftDayType.noOfDay',
            'workShiftDayType.startTime',
            'workShiftDayType.endTime',
            'workShiftDayType.workHours',
            'workShiftDayType.breakTime',
            'workShiftDayType.hasMidnightCrossOver',
            'workShiftDayType.halfDayLength',
            'workShiftDayType.gracePeriod',
            'workShiftDayType.isOTEnabled',
            'workShiftDayType.isBehaveAsNonWorkingDay',
            'workShiftDayType.inOvertime',
            'workShiftDayType.outOvertime',
            'workShiftDayType.deductLateFromOvertime',
            'workShiftDayType.minimumOT',
            'workShiftDayType.roundOffMethod',
            'workShiftDayType.roundOffToNearest'
        ]);


        // return if employee relate work pattern exist
        if (!empty($employeeWorkPatternShift)) {
            return $employeeWorkPatternShift;
        }

        $employeeJob = $this->getEmployeeJob($employeeId, $dateObject->format('Y-m-d'), ['calendarId', 'locationId']);
        // check whether employee has a job, if job not exist ignore that employee
        if (empty($employeeJob)) {
            return null;
        }

        // check location shift
        // $locationWorkPatternShift = DB::table('workPatternLocation')
        // ->join('workPatternWeek', 'workPatternWeek.workPatternId', '=', 'workPatternLocation.workPatternId')
        // ->join('workPatternWeekDay', 'workPatternWeekDay.workPatternWeekId', '=', 'workPatternWeek.id')
        // ->join('workShifts', 'workShifts.id', '=', 'workPatternWeekDay.workShiftId')
        // ->join('workShiftDayType', 'workShiftDayType.workShiftId', '=', 'workShifts.id')
        // ->where('workPatternLocation.locationId', $employeeJob->locationId)
        // ->where('workPatternWeekDay.dayTypeId', $dayNameId)
        // ->where('workShiftDayType.dayTypeId', $calendarDayTypeId)
        // ->first([
        //     'workShifts.id',
        //     'workShifts.shiftType',
        //     'workShiftDayType.dayTypeId',
        //     'workShiftDayType.noOfDay',
        //     'workShiftDayType.startTime',
        //     'workShiftDayType.endTime',
        //     'workShiftDayType.workHours',
        //     'workShiftDayType.breakTime',
        //     'workShiftDayType.hasMidnightCrossOver',
        //     'workShiftDayType.halfDayLength',
        //     'workShiftDayType.gracePeriod',
        //     'workShiftDayType.isOTEnabled',
        //     'workShiftDayType.isBehaveAsNonWorkingDay',
        //     'workShiftDayType.inOvertime',
        //     'workShiftDayType.outOvertime',
        //     'workShiftDayType.deductLateFromOvertime',
        //     'workShiftDayType.minimumOT',
        //     'workShiftDayType.roundOffMethod',
        //     'workShiftDayType.roundOffToNearest'
        // ]);

        // // return if adhocWorkshift exist
        // if (!empty($locationWorkPatternShift)) {
        //     return $locationWorkPatternShift;
        // }

        return null;
    }

    /**
     * Get employee adhocWorkshift
     * 
     * @param  int $employeeId
     * @param  Carbon $date carbon date object
     * 
     * @return object | null | \Throwable
     */
    protected function getAdhocShift($employeeId, $dateObject)
    {
        try {
            return DB::table('adhocWorkshifts')
                ->join('workShifts', 'workShifts.id', '=', 'adhocWorkshifts.workShiftId')
                ->where('adhocWorkshifts.employeeId', $employeeId)->where('adhocWorkshifts.date', $dateObject->format('Y-m-d'))
                ->first(['workShifts.id', 'workShifts.noOfDay', 'workShifts.startTime', 'workShifts.endTime', 'workShifts.workHours', 'workShifts.breakTime', 'workShifts.hasMidnightCrossOver']);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Get employee leave for given date
     * 
     * @param  int $employeeId
     * @param  Carbon $date carbon date object
     * 
     * @return object | null | \Throwable
     */
    protected function getEmployeeLeave($employeeId, $date)
    {
        return DB::table('leaveRequestDetail')
            ->join('leaveRequestEntitlement', 'leaveRequestEntitlement.leaveRequestDetailId', '=', 'leaveRequestDetail.id')
            ->join('leaveRequest', 'leaveRequest.id', '=', 'leaveRequestDetail.leaveRequestId')
            ->join('leaveType', 'leaveType.id', '=', 'leaveRequest.leaveTypeId')
            ->where('leaveRequest.employeeId', $employeeId)
            ->where('leaveRequestDetail.leaveDate', $date)
            ->whereNotIn('leaveRequestDetail.status', ['REJECTED', 'CANCELED'])
            ->get(['leaveRequest.id', 'leaveRequest.leaveTypeId', 'leaveType.shortLeaveAllowed', 'leaveRequestDetail.leavePeriodType', 'leaveRequestDetail.leaveDate', 'leaveRequestDetail.status', 'leaveRequestEntitlement.entitlePortion', 'leaveType.name as leaveTypeName']);
    }

    /**
     * Get employee leave for given date
     * 
     * @param  int $employeeId
     * @param  Carbon $date carbon date object
     * 
     * @return object | null | \Throwable
     */
    protected function getEmployeeApprovedLeave($employeeId, $date)
    {
        return DB::table('leaveRequestDetail')
            ->join('leaveRequestEntitlement', 'leaveRequestEntitlement.leaveRequestDetailId', '=', 'leaveRequestDetail.id')
            ->join('leaveRequest', 'leaveRequest.id', '=', 'leaveRequestDetail.leaveRequestId')
            ->join('leaveType', 'leaveType.id', '=', 'leaveRequest.leaveTypeId')
            ->where('leaveRequest.employeeId', $employeeId)
            ->where('leaveRequestDetail.leaveDate', $date)
            ->whereNotIn('leaveRequestDetail.status', ['REJECTED', 'CANCELED', 'PENDING'])
            ->get(['leaveRequest.id', 'leaveRequest.leaveTypeId', 'leaveType.shortLeaveAllowed', 'leaveRequestDetail.leavePeriodType', 'leaveRequestDetail.leaveDate', 'leaveRequestDetail.status', 'leaveRequestEntitlement.entitlePortion', 'leaveType.name as leaveTypeName']);
    }

    /**
     * Get employee short leave for given date
     * 
     * @param  int $employeeId
     * @param  Carbon $date carbon date object
     * 
     * @return object | null | \Throwable
     */
    protected function getEmployeeShortLeave($employeeId, $date)
    {
        $finalShortLeaveData = [];
        $shorLeaveData = DB::table('shortLeaveRequest')
        ->where('shortLeaveRequest.employeeId', $employeeId)
            ->where('shortLeaveRequest.date', $date)
            ->whereIn('shortLeaveRequest.currentState', [1,2])
            ->get(['shortLeaveRequest.id as shortLeaveRequestId', 'shortLeaveRequest.shortLeaveType as leavePeriodType', 'shortLeaveRequest.date as leaveDate', 'shortLeaveRequest.workflowInstanceId', 'shortLeaveRequest.currentState', 'shortLeaveRequest.employeeId']);

        foreach ($shorLeaveData as $key => $shortLeave) {
            if (empty($shortLeave->workflowInstanceId)) {
                $finalShortLeaveData[] = $shortLeave;
            } else {
                $instanceData = DB::table('workflowInstance')
                ->where('workflowInstance.id', $shortLeave->workflowInstanceId)
                    ->where('workflowInstance.isDelete', false)->first();
                $instanceData = (array) $instanceData;
                $priorState = $instanceData['currentStateId'];

                //check whether the workflow in failure state
                // $workflowData = DB::table('workflowDefine')
                // ->where('workflowDefine.id', $instanceData['workflowId'])
                // ->where('workflowDefine.isDelete', false)->first();

                // $workflowData = (array) $workflowData;
                // $failureStates = json_decode($workflowData['failureStates']);

                if (!in_array($priorState, [3, 4])) {
                    $finalShortLeaveData[] = $shortLeave;
                }
            }
        }

        return $finalShortLeaveData;
    }

    /**
     * Get employee leave amount for given date
     * 
     * @param  int $employeeId
     * @param  Carbon $date carbon date object
     * 
     * @return object | null | \Throwable
     */
    protected function getEmployeeLeaveAmount($employeeId, $date)
    {
        return DB::table('leaveRequestDetail')
            ->join('leaveRequestEntitlement', 'leaveRequestEntitlement.leaveRequestDetailId', '=', 'leaveRequestDetail.id')
            ->join('leaveRequest', 'leaveRequest.id', '=', 'leaveRequestDetail.leaveRequestId')
            ->where('leaveRequest.employeeId', $employeeId)
            ->where('leaveRequestDetail.leaveDate', $date)
            ->whereNotIn('leaveRequestDetail.status', ['REJECTED', 'CANCELED'])
            ->sum('leaveRequestEntitlement.entitlePortion');
    }

    protected function getEmployeeIdsByLeaveGroupId($leaveEmployeeGroupId)
    {
        $leaveEmployeeGroup = DB::table('leaveEmployeeGroup')
            ->where('id', $leaveEmployeeGroupId)
            ->first();

        if (empty($leaveEmployeeGroup)) {
            return [];
        }

        $query = DB::table('employee')
            ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
            ->leftJoin('employmentStatus', 'employmentStatus.id', '=', 'employeeJob.employmentStatusId');

        $jobTitles = !empty($leaveEmployeeGroup->jobTitles) ? json_decode($leaveEmployeeGroup->jobTitles, true) : [];
        if (!empty($jobTitles) && !in_array('*', $jobTitles)) {
            $query->whereIn('employeeJob.jobTitleId', $jobTitles);
        }

        $employmentStatuses = !empty($leaveEmployeeGroup->employmentStatuses) ? json_decode($leaveEmployeeGroup->employmentStatuses) : [];
        if (!empty($employmentStatuses) && !in_array('*', $employmentStatuses)) {
            $query->whereIn('employeeJob.employmentStatusId', $employmentStatuses);
        }

        $genders = !empty($leaveEmployeeGroup->genders) ? json_decode($leaveEmployeeGroup->genders, true) : [];
        if (!empty($genders) && !in_array('*', $genders)) {
            $query->whereIn('employee.genderId', $genders);
        }

        $locations = !empty($leaveEmployeeGroup->locations) ? json_decode($leaveEmployeeGroup->locations, true) : [];
        if (!empty($locations) && !in_array('*', $locations)) {
            $query->whereIn('employeeJob.locationId', $locations);
        }

        $minServicePeriod = !empty($leaveEmployeeGroup->minServicePeriod) ? intval($leaveEmployeeGroup->minServicePeriod) : null;
        if (!is_null($minServicePeriod)) {
            $query->where('employeeJob.employeeJourneyType', '!=', 'RESIGNATIONS');
            $query->whereRaw('TIMESTAMPDIFF(MONTH, employeeJob.effectiveDate, NOW()) >= ' . $minServicePeriod);
        }

        //TODO: need to recheck 
        $minPemenancyPeriod = !empty($leaveEmployeeGroup->minPemenancyPeriod) ? intval($leaveEmployeeGroup->minPemenancyPeriod) : null;
        if (!is_null($minPemenancyPeriod)) {
            $query->where('employeeJob.employeeJourneyType', '!=', 'RESIGNATIONS');
            $query->whereRaw('TIMESTAMPDIFF(MONTH, DATE_ADD(employeeJob.effectiveDate, INTERVAL employmentStatus.period CASE employmentStatus.periodUnit WHEN "YEARS" THEN YEAR WHEN "MONTHS" THEN MONTH ELSE DAY END), NOW()) >= ' . $minPemenancyPeriod);
        }

        return $query->get(['employee.id', 'employee.hireDate', 'employee.hireDate', 'employee.currentJobsId', 'employee.recentHireDate']);
    }

    protected function getEmployeeIdsByWorkflowGroupId($workflowEmployeeGroupId)
    {
        $workflowEmployeeGroup = DB::table('workflowEmployeeGroup')
            ->where('id', $workflowEmployeeGroupId)
            ->first();

        if (empty($workflowEmployeeGroup)) {
            return [];
        }

        $query = DB::table('employee')
            ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId');

        $jobTitles = !empty($workflowEmployeeGroup->jobTitles) ? json_decode($workflowEmployeeGroup->jobTitles, true) : [];
        if (!empty($jobTitles) && !in_array('*', $jobTitles)) {
            $query->whereIn('employeeJob.jobTitleId', $jobTitles);
        }

        $employmentStatuses = !empty($workflowEmployeeGroup->employmentStatuses) ? json_decode($workflowEmployeeGroup->employmentStatuses) : [];
        if (!empty($employmentStatuses) && !in_array('*', $employmentStatuses)) {
            $query->whereIn('employeeJob.employmentStatusId', $employmentStatuses);
        }

        $departments = !empty($workflowEmployeeGroup->departments) ? json_decode($workflowEmployeeGroup->departments, true) : [];
        if (!empty($departments) && !in_array('*', $departments)) {
            $query->whereIn('employeeJob.departmentId', $departments);
        }

        $locations = !empty($workflowEmployeeGroup->locations) ? json_decode($workflowEmployeeGroup->locations, true) : [];
        if (!empty($locations) && !in_array('*', $locations)) {
            $query->whereIn('employeeJob.locationId', $locations);
        }

        $divisions = !empty($workflowEmployeeGroup->divisions) ? json_decode($workflowEmployeeGroup->divisions, true) : [];
        if (!empty($divisions) && !in_array('*', $divisions)) {
            $query->whereIn('employeeJob.divisionId', $divisions);
        }

        $reportingPersons = !empty($workflowEmployeeGroup->reportingPersons) ? json_decode($workflowEmployeeGroup->reportingPersons, true) : [];
        if (!empty($reportingPersons) && !in_array('*', $reportingPersons)) {
            $query->whereIn('employeeJob.reportsToEmployeeId', $reportingPersons);
        }

        $employeeDataSet = $query->get(['employee.id', 'employee.hireDate', 'employee.hireDate', 'employee.currentJobsId']);
        $employeeIdArray = [];
        foreach ($employeeDataSet as $key => $employee) {
            $employee = (array) $employee;
            $employeeIdArray[] = $employee['id'];
        }

        return $employeeIdArray;
    }


    protected function getWorkPatternByShiftId($shiftId)
    {
        return DB::table('workPatternWeekDay')
            ->join('workPatternWeek', 'workPatternWeek.id', '=', 'workPatternWeekDay.workPatternWeekId')
            ->join('workPattern', 'workPattern.id', '=', 'workPatternWeek.workPatternId')
            ->where('workPatternWeekDay.workShiftId', $shiftId)
            ->first(['workPattern.name', 'workPattern.description']);
    }

    /**
     * Update employee record updatedAt column
     * 
     * @param  int $employeeId
     * 
     * @return object | null | \Throwable
     */
    protected function updateEmployeeRecordUpdatedAtColumn($employeeId)
    {
        $updatedAtTimeStamp = Carbon::now()->toDateTimeString();
        return DB::table('employee')
            ->where('id', $employeeId)
            ->update(['updatedAt' => $updatedAtTimeStamp]);
    }

    public function checkSelfServiceRecordLockIsEnable($dateArray, $selfServiceType) {

        $lockRecordsCount = 0;

        foreach ($dateArray as $key => $date) {
            //get matching self service lock records
            $selfServiceLockRecord = DB::table('selfServiceLockConfigs')
                ->select('selfServiceLockConfigs.*','selfServiceLockDatePeriods.fromDate', 'selfServiceLockDatePeriods.toDate')
                ->join('selfServiceLockDatePeriods', 'selfServiceLockDatePeriods.id', '=', 'selfServiceLockConfigs.selfServiceLockDatePeriodId')
                ->where('status', 'LOCKED')->where('selfServiceLockDatePeriods.fromDate', '<=',$date)->where('selfServiceLockDatePeriods.toDate','>=', $date)->first();

            if (!empty($selfServiceLockRecord)) {
                $selfServiceLockRecord = (array) $selfServiceLockRecord; 
                $selfServicesStatus = (!empty($selfServiceLockRecord)) ? (array)json_decode($selfServiceLockRecord['selfServicesStatus']) : [];

                if (isset($selfServicesStatus[$selfServiceType]) && $selfServicesStatus[$selfServiceType]) {
                    error_log(json_encode($selfServiceLockRecord));
                    $lockRecordsCount ++;
                }
            }
        }

        if ($lockRecordsCount > 0) {
            return true;
        }

        return false;
    }

    public function generateEmployeeNumber($entityId)
    {
        $selectedFields = ['id', 'parentEntityId'];

        $orgEntities = DB::table("orgEntity")->where('isDelete', false)->orderBy("parentEntityId", "DESC")->get($selectedFields);

        $entity = $orgEntities->firstWhere('id', $entityId);

        if (is_null($entity)) {
            return [
                'error' => true,
                'message' => 'Please configure employee number'
            ];
        }

        $numberConfigs = DB::table("employeeNumberConfiguration")->get(['id', 'entityId', 'prefix', 'nextNumber', 'numberLength']);

        $currentEntityId = $entityId;

        $configRecord = null;

        for ($i = 0; $i < count($orgEntities); $i++) {
            $currentEntity = $orgEntities->firstWhere('id', $currentEntityId);
            if (is_null($currentEntity) || is_null($currentEntity->parentEntityId)) {
                $configRecord = $numberConfigs->firstWhere('entityId', $currentEntityId);
                break;
            }
            $configRecord = $numberConfigs->firstWhere('entityId', $currentEntityId);
            if ($configRecord) {
                break;
            }
            $currentEntityId = $currentEntity->parentEntityId;
        }

        if (is_null($configRecord)) {
            return [
                'error' => true,
                'message' => 'Please configure employee number'
            ];
        }

        $numberLength = strlen((string)$configRecord->nextNumber);
        if ($numberLength > $configRecord->numberLength) {
            return [
                'error' => true,
                'message' => 'Employee number is exceeded'
            ];
        }

        $postfix = str_pad($configRecord->nextNumber, $configRecord->numberLength, "0", STR_PAD_LEFT);

        return [
            'error' => false,
            'message' => 'success',
            'data' => [
                'employeeNumber' => $configRecord->prefix . $postfix,
                'numberConfigId' => $configRecord->id,
                'configRecord' => $configRecord
            ]
        ];
    }

    public function incrementEmployeeNumber($numberConfigId)
    {
        if (is_null($numberConfigId)) {
            return true;
        }

        return DB::table('employeeNumberConfiguration')->where('id', $numberConfigId)
            ->update([
                'nextNumber' => DB::raw('nextNumber + 1')
            ]);
    }

    /**
     * Get children of given node
     * $parentId = 1
     * $nodes = [{ id => 1, parentEntityId => null }, { id => 2, parentEntityId => 1 }]
     */
    function findChildren($parentId, $nodes)
    {
        $result = [];

        foreach ($nodes as $node) {
            if ($node->parentEntityId === $parentId) {
                $result[] = $node->id;
                $children = $this->findChildren($node->id, $nodes);
                $result = array_merge($result, $children);
            }
        }

        return $result;
    }
}
