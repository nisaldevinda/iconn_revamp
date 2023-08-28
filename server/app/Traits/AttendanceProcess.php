<?php

namespace App\Traits;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
trait AttendanceProcess
{
    use JobResponser;
    use EmployeeHelper;
    use ConfigHelper;

    public function processAttendanceForTenant($dateObj, $isManualProcess = false)
    {
        try {
            // get date by Y-m-d format
            $date = $dateObj->format('Y-m-d');

            // get Day name
            $dayName = $dateObj->format('l');

            // get all locations
            $locationCollection = DB::table('location')->get(['id', 'timeZone']);

            // get company timezone
            $company = DB::table('company')->first('timeZone');

            // get tenant calenders
            $calenderCollection = DB::table('workCalendar')->join('workCalendarDateNames', 'workCalendar.id', '=', 'workCalendarDateNames.calendarId')->leftJoin('dayOfWeek', 'workCalendarDateNames.dayOfWeekId', '=', 'dayOfWeek.id')
                            ->get(['workCalendar.id', 'dayOfWeek.dayName as name', 'workCalendarDateNames.workCalendarDayTypeId']);

            // get calendar holidays and ignore working days
            $holidayCollection = DB::table('workCalendarSpecialDays')->where('date', $date)->where('workCalendarDayTypeId', '!=', 1)->get(['calendarId', 'workCalendarDayTypeId']);

            $recordedEmployeeIds = [];

            if (!$isManualProcess) {
                // get recorded employee Ids
                $recordedEmployeeIds = DB::table('attendance_summary')->where('date', $date)->pluck('employeeId');
            }

            $employeeIds = DB::table('employee')->where('isActive', 1)->whereNotIn('id', $recordedEmployeeIds)->pluck('id');

            // get current date according to company
            $companyDate = $dateObj->copy()->tz($company->timeZone);

            $attendanceRecords = [];

            foreach ($employeeIds as $employeeId) {

                // get employee current job
                $employeeJob = $this->getEmployeeJob($employeeId, $companyDate->format('Y-m-d'), ['calendarId', 'locationId']);

                // check whether employee has a job, if job not exist ignore that employee
                if (empty($employeeJob)) {
                    continue;
                }

                // get location
                $locationId = empty($employeeJob->locationId) ? null : $employeeJob->locationId;

                // if location not exist get default company timezone
                $timeZone = empty($locationId) ? $company->timeZone : $locationCollection->firstWhere('id', $locationId)->timeZone;

                // get location current date time object
                $locationCurrentDateObj = ($isManualProcess) ? $dateObj : $dateObj->copy()->tz($timeZone);

                // compare with location date time
                if (!$dateObj->isSameDay($locationCurrentDateObj)) {
                    continue;
                }

                // if calendar not set get default calendar id
                $calendarId = empty($employeeJob->calendarId) ? 1 : $employeeJob->calendarId;

                // check holiday for given date & employee calendar
                $holiday = $holidayCollection->firstWhere('calendarId', $calendarId);

                $calendarDayTypeId = null;

                if (empty($holiday)) {
                    // get relevent calendar date
                    $calendarDay = $calenderCollection->where('id', '=', $calendarId)->where('name', '=', strtolower($dayName))->first();
                    $calendarDayTypeId = $calendarDay->workCalendarDayTypeId;
                } else { // if holiday
                    $calendarDayTypeId = $holiday->workCalendarDayTypeId;
                }

                $shift = $this->getEmployeeWorkShift($employeeId, $dateObj, $calendarDayTypeId);

                // get leaves
                $leaves = $this->getEmployeeLeave($employeeId, $date);
                
                //get short leaves
                $shortLeaves = $this->getEmployeeShortLeave($employeeId, $date);
               
                if (sizeof($leaves) > 0 && sizeof($shortLeaves) > 0) {
                    $leaves = array_merge($leaves,$shortLeaves);
                }

                if (sizeof($leaves) === 0 && !empty($shortLeaves)) {
                    $leaves = $shortLeaves;
                }

                // get keave summary
                $leaveSummary = $this->getLeaveSummary($leaves, $shift);

                $attendanceRecord = $this->getAttendanceRecord($dateObj, $employeeId, $timeZone, $calendarDayTypeId, $shift, $leaveSummary);

                $attendanceRecords[] = $attendanceRecord;
            }

            if ($isManualProcess) {
                foreach ($attendanceRecords as &$attendanceRecord) {
                    $query = DB::table('attendance_summary')->where('employeeId', $attendanceRecord['employeeId'])->where('date', $dateObj->format('Y-m-d'));
                    $dbRecord = $query->first();

                    if (empty($dbRecord)) { // if attendance record not exist
                        DB::table('attendance_summary')->insert($attendanceRecord);
                    } else {
                        $record = $this->updateAttendanceRecord($attendanceRecord, $dbRecord->actualIn, $dbRecord->actualOut, $dbRecord->id, $shift);
                        $query->update($record);
                        $this->calculateAttendanceRelatedOTDetails($dbRecord->id, $record);
                    }
                }
            } else {
                $attendanceCollection = collect($attendanceRecords);
                $attendanceChunks = $attendanceCollection->chunk(500);
                foreach ($attendanceChunks as $chunk) {
                    DB::table('attendance_summary')->insert($chunk->toArray());
                }
            }

            return $this->jobResponse(false);
           
        } catch (Exception $e) {
            Log::error("Attendance process error : " . report($e));
            return $this->jobResponse(true, $e->getMessage());
        }
    }

    /**
     * Run employee attendance for multiple dates
     */
    public function processEmployeeAttendance($employeeId, $dates)
    {
        try {

            DB::beginTransaction();

            // get all locations
            $locationCollection = DB::table('location')->get(['id', 'timeZone']);

            // get company timezone
            $company = DB::table('company')->first('timeZone');

            // get calendars special days
            $calendarSpecialDaysCollection = DB::table('workCalendarSpecialDays')
                ->whereIn('date', $dates)
                ->where('workCalendarDayTypeId', '!=', 1)
                ->get(['calendarId', 'workCalendarDayTypeId', 'date']);

            foreach ($dates as $date) {
                $this->processemployeeAttendanceForDate($employeeId, $date, $company, $locationCollection, $calendarSpecialDaysCollection);
            }
            DB::commit();
            return $this->jobResponse(false);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->jobResponse(true, $e->getMessage());
        }
    }

    /**
     * Run attendance for multiple employees and multiple dates
     * 
     * @param  array $employeeIds
     * @param  array $dates ['2022-01-01', '2022-01-02
     * 
     * @return array ['error' => false, $message => null]
     */
    public function processEmployeesAttendance($employeeIds, $dates)
    {
        try {

            DB::beginTransaction();

            // get all locations
            $locationCollection = DB::table('location')->get(['id', 'timeZone']);

            // get company timezone
            $company = DB::table('company')->first('timeZone');

            // get calendars special days
            $calendarSpecialDaysCollection = DB::table('workCalendarSpecialDays')
            ->whereIn('date', $dates)
                ->where('workCalendarDayTypeId', '!=', 1)
                ->get(['calendarId', 'workCalendarDayTypeId', 'date']);

            foreach ($employeeIds as $employeeId) {
                foreach ($dates as $date) {
                    $this->processemployeeAttendanceForDate($employeeId, $date, $company, $locationCollection, $calendarSpecialDaysCollection);
                }
            }

            DB::commit();
            return $this->jobResponse(false);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->jobResponse(true, $e->getMessage());
        }
    }

    /**
     * Insert or Update attendance for given date
     *
     * @param  int $employeeId
     * @param  string $date 2022-01-01
     * @param  object $companyObject
     * @param  \Illuminate\Support\Collection $locationCollection
     * @param  \Illuminate\Support\Collection $specialDaysCollection
     */
    private function processemployeeAttendanceForDate($employeeId, $date, $companyObject, $locationCollection, $specialDaysCollection)
    {
        $dateObj = Carbon::parse($date, 'UTC');
        // get day name
        $dayName = $dateObj->format('l');
        // get employee current job
        $employeeJob = $this->getEmployeeJob($employeeId, $date, ['calendarId', 'locationId']);

        // check whether employee has a job, if job not exist ignore that employee
        if (empty($employeeJob)) {
            return false;
        }

        // if calendar not set get default calendar id
        $calendarId = empty($employeeJob->calendarId) ? 1 : $employeeJob->calendarId;
        // get location
        $locationId = empty($employeeJob->locationId) ? null : $employeeJob->locationId;

        // if location not exist get default company timezone
        $timeZone = empty($locationId) ? $companyObject->timeZone : $locationCollection->firstWhere('id', $locationId)->timeZone;

        $locationDateObject = Carbon::now($timeZone);
        // ignore future dates
        if ($dateObj->greaterThan($locationDateObject)) {
            return false;
        }

        $calendarDayTypeId = null;

        // to check whether holiday 
        $holiday = $specialDaysCollection->where('calendarId', $calendarId)->where('date', $date)->first();

        if (empty($holiday)) {
            $calendarDayTypeId = $this->getCalendarDayTypeId($calendarId, $dayName);
        } else { // if holiday
            $calendarDayTypeId = $holiday->workCalendarDayTypeId;
        }

        $shift = $this->getEmployeeWorkShift($employeeId, $dateObj, $calendarDayTypeId);

        // get leaves
        $leaves = $this->getEmployeeLeave($employeeId, $date);
        //get short leaves
        $shortLeaves = $this->getEmployeeShortLeave($employeeId, $date);

        if (sizeof($leaves) > 0 && sizeof($shortLeaves) > 0) {
            $leaves = array_merge($leaves, $shortLeaves);
        }

        if (sizeof($leaves) === 0 && !empty($shortLeaves)) {
            $leaves = $shortLeaves;
        }

        // get keave summary
        $leaveSummary = $this->getLeaveSummary($leaves, $shift);

        $attendanceRecord = $this->getAttendanceRecord($dateObj, $employeeId, $timeZone, $calendarDayTypeId, $shift, $leaveSummary);

        $query = DB::table('attendance_summary')->where('employeeId', $attendanceRecord['employeeId'])->where('date', $attendanceRecord['date']);
        $dbRecord = $query->first();

        if (empty($dbRecord)) { // if attendance record not exist
            DB::table('attendance_summary')->insert($attendanceRecord);
        } else {
            $record = $this->updateAttendanceRecord($attendanceRecord, $dbRecord->actualIn, $dbRecord->actualOut, $dbRecord->id, $shift);
            $query->update($record);
            $this->calculateAttendanceRelatedOTDetails($dbRecord->id, $record);
        }

        return true;
    }

    /**
     * Get attendance record
     */
    protected function getAttendanceRecord($dateObj, $employeeId, $timeZone, $dateTypeId, $shift, $leaveSummary = [])
    {

        //get related base day type of calander day type
        $baseDayType = null;
        if (!empty($dateTypeId)) {
            $relatedBaseDayType =  DB::table('workCalendarDayType')->where('id', $dateTypeId)->first();
            $baseDayType = (!is_null($relatedBaseDayType)) ? $relatedBaseDayType->baseDayTypeId : null;
        }


        return [
            'date' => $dateObj->format('Y-m-d'),
            'employeeId' => $employeeId,
            'timeZone' => $timeZone,
            'dayTypeId' => $dateTypeId,
            'baseDayType' => $baseDayType,
            'shiftId' => $this->getShiftId($shift),
            'hasMidnightCrossOver' => $this->hasMidnightCrossOver($shift),
            'isExpectedToPresent' => $this->isExpectedToPresent($shift, $leaveSummary),
            'expectedLeaveAmount' => $this->expectedLeaveAmount($shift, $leaveSummary),
            'expectedIn' => $this->expectedIn($dateObj, $shift, $leaveSummary),
            'expectedOut' => $this->expectedOut($dateObj, $shift, $leaveSummary),
            'actualIn' => null,
            'actualInUTC' => null,
            'actualOut' => null,
            'actualOutUTC' => null,
            'isPresent' => false,
            'isLateIn' => false,
            'isEarlyOut' => false,
            'expectedWorkTime' => $this->expectedWorkTime($shift, $leaveSummary),
            'workTime' => 0,
            'breakTime' => 0,
            'isFullDayLeave' => isset($leaveSummary['hasFullDayLeave']) ? $leaveSummary['hasFullDayLeave'] : false,
            'isHalfDayLeave' => (!empty($leaveSummary['hasFirstHalfDayLeave']) || !empty($leaveSummary['hasSecondHalfDayLeave'])) ? true : false,
            'isShortLeave' => (!empty($leaveSummary['hasInShortLeave']) || !empty($leaveSummary['hasOutShortLeave'])) ? true : false,
            'isNoPay' => $this->isNoPay($shift, $leaveSummary)
        ];
    }

    protected function updateAttendanceRecord($attendanceRecord, $actualIn = null, $actualOut = null, $summaryId, $shift)
    {
        $timeZone = $attendanceRecord['timeZone'];
        $workTime = (is_null($actualIn) || is_null($actualOut)) ? 0 : $this->workTimeInMinitues($actualOut, $actualIn);
        $breakTime = $this->calculateTotalBreakTime($summaryId, $attendanceRecord['employeeId'], $attendanceRecord['date']);
        $workTime = ($workTime - $breakTime);
        $gracePeriod = (!is_null($shift) && !is_null($shift->gracePeriod)) ? (int) $shift->gracePeriod : 0;

        //calculate late in
        if (!is_null($shift) && $shift->shiftType == 'FLEXI') {
            $lateInMinutes = (!is_null($shift) && $shift->isBehaveAsNonWorkingDay) ? 0 : $this->lateInForFlexiShifts($shift, $actualIn, $attendanceRecord);
        } else {
            $lateInMinutes = (!is_null($shift) && $shift->isBehaveAsNonWorkingDay) ? 0 : $this->lateIn($attendanceRecord['expectedIn'], $actualIn);

        }

        //calculate early out
        if (!is_null($shift) && $shift->shiftType == 'FLEXI') {
            $earlyOutInMinutes = (!is_null($shift) && $shift->isBehaveAsNonWorkingDay) ? 0 : $this->earlyOutForFlexiShifts($attendanceRecord['expectedWorkTime'], $workTime);
        } else {
            $earlyOutInMinutes = (!is_null($shift) && $shift->isBehaveAsNonWorkingDay) ? 0 : $this->earlyOut($attendanceRecord['expectedOut'], $actualOut);
        }



        if (!is_null($shift) && $shift->isBehaveAsNonWorkingDay) {
            $shiftWiseWorkTime = [
                'preShiftWorkTime' => 0,
                'withinShiftWorkTime' => 0,
                'postShiftWorkTime' =>$workTime
            ];
        } else {

            $expectedIn = $attendanceRecord['expectedIn'];
            $expectedOut = $attendanceRecord['expectedOut'];

            if (!is_null($shift) && $shift->shiftType == 'FLEXI' && !is_null($actualIn)) {
                $expectedIn = $actualIn;
                $actualInObj = Carbon::parse($actualIn);
                $expectedWorkTime = !is_null($attendanceRecord['expectedWorkTime']) ? (int) $attendanceRecord['expectedWorkTime'] : 0;
                $expectOutObj = $actualInObj->copy()->addMinutes($expectedWorkTime);
                $expectedOut = $expectOutObj->format('Y-m-d H:i');
            }

            $shiftWiseWorkTime = $this->calculateWorkTimeAccordingToShiftPeriod($summaryId, $attendanceRecord['employeeId'], $attendanceRecord['date'], $expectedIn, $expectedOut);
        }

        $attendanceRecord['actualIn'] = $actualIn;
        $attendanceRecord['actualInUTC'] = is_null($actualIn) ? null : Carbon::parse($actualIn, $timeZone)->copy()->tz('UTC');
        $attendanceRecord['actualOut'] = $actualOut;
        $attendanceRecord['actualOutUTC'] = is_null($actualOut) ? null : Carbon::parse($actualOut, $timeZone)->copy()->tz('UTC');
        $attendanceRecord['isPresent'] = (!is_null($actualIn) || !is_null($actualOut));
        $attendanceRecord['isLateIn'] = ($lateInMinutes > 0 && $lateInMinutes > $gracePeriod);
        $attendanceRecord['lateIn'] = ($lateInMinutes > 0 && $lateInMinutes > $gracePeriod) ? $lateInMinutes : 0;
        $attendanceRecord['isEarlyOut'] = ($earlyOutInMinutes > 0);
        $attendanceRecord['earlyOut'] = ($earlyOutInMinutes > 0) ? $earlyOutInMinutes : 0;
        $attendanceRecord['workTime'] = $workTime;
        $attendanceRecord['preShiftWorkTime'] = $shiftWiseWorkTime['preShiftWorkTime'];
        $attendanceRecord['withinShiftWorkTime'] = $shiftWiseWorkTime['withinShiftWorkTime'];
        $attendanceRecord['postShiftWorkTime'] = $shiftWiseWorkTime['postShiftWorkTime'];
        $attendanceRecord['breakTime'] = (is_null($actualIn) && is_null($actualOut)) ? 0 : $breakTime;
        $attendanceRecord['isNoPay'] = (!is_null($actualIn) || !is_null($actualOut)) ? false : $attendanceRecord['isNoPay'];

        return $attendanceRecord;
    }


    protected function isExpectedToPresent($shift, $leaveSummary = [])
    {
        if (is_null($shift)) {
            return false;
        } 
        
        if ($shift->isBehaveAsNonWorkingDay) {
            if ($shift->noOfDay > 0) {
                return true;
            }
            return false;
        }

        $expectedWorkTime = $this->expectedWorkTime($shift, $leaveSummary);

        return ($expectedWorkTime > 0);
    }

    protected function getShiftId($shift)
    {
        // if shift not exist
        if (is_null($shift)) {
            return null;
        }

        return isset($shift->id) ? $shift->id : null;
    }

    private function processLeaveSummary($employeeId, $date, $shift) {
         // get leaves
         $leaves = $this->getEmployeeLeave($employeeId, $date);
         //get short leaves
         $shortLeaves = $this->getEmployeeShortLeave($employeeId, $date);
 
         if (sizeof($leaves) > 0 && sizeof($shortLeaves) > 0) {
             $leaves = array_merge($leaves, $shortLeaves);
         }
 
         if (sizeof($leaves) === 0 && !empty($shortLeaves)) {
             $leaves = $shortLeaves;
         }
 
         // get keave summary
         $leaveSummary = $this->getLeaveSummary($leaves, $shift);

         return $leaveSummary;
    }

    protected function hasMidnightCrossOver($shift)
    {
        // if shift not exist
        if (is_null($shift)) {
            return 0;
        }

        return isset($shift->hasMidnightCrossOver) ? $shift->hasMidnightCrossOver : 0;
    }

    protected function expectedLeaveAmount($shift, $leaveSummary = [])
    {
        // if shift not exist
        if (is_null($shift)) {
            return 0;
        }

        if (!isset($shift->noOfDay) || (isset($shift->noOfDay) && empty($shift->noOfDay))) {
            return 0;
        }

        // if leave not exist
        if ($leaveSummary['leavePotion'] == 0) {
            return $shift->noOfDay;
        }

        $appliedLeavePotion = $leaveSummary['leavePotion'];

        $leavePotion = ($shift->noOfDay - $appliedLeavePotion);

        return ($leavePotion > 0) ? $leavePotion : 0;
    }

    protected function expectedIn($dateObject, $shift, $leaveSummary)
    {
        $shortLeaveDuration = $this->getConfigValue('short_leave_duration');

        if (is_null($shift)) {
            return null;
        }

        if ($shift->shiftType == 'FLEXI') {
            return null;
        }

        if ($shift->isBehaveAsNonWorkingDay) {
            return null;
        }

        if (empty($shift->startTime)) {
            return null;
        }

        $noOfDay = !empty($shift->noOfDay) ? $shift->noOfDay : 0;
        $workHours = !empty($shift->workHours) ? $shift->workHours : 0;

        $date = $dateObject->isoFormat('YYYY-MM-DD');
        $startDateTime = $date . " " . $shift->startTime;

        if ($leaveSummary['hasFirstHalfDayLeave']) {
            $inDateTime = Carbon::createFromFormat('Y-m-d H:i',  $startDateTime);
            if ($noOfDay == 0.5) { // if shift is half day
                return $startDateTime;
            } else {
                $inObj = $inDateTime->copy()->addMinutes(($workHours * 0.5));
                return $inObj->format('Y-m-d H:i');
            }
        } else if ($leaveSummary['hasInShortLeave']) {
            $inDateTime = Carbon::createFromFormat('Y-m-d H:i',  $startDateTime);
            $inObj = $inDateTime->copy()->addMinutes($shortLeaveDuration);
            return $inObj->format('Y-m-d H:i');
        } else {
            return $startDateTime;
        }
    }

    protected function expectedOut($dateObject, $shift, $leaveSummary)
    {
        $shortLeaveDuration = $this->getConfigValue('short_leave_duration');

        if (is_null($shift)) {
            return null;
        }

        if ($shift->shiftType == 'FLEXI') {
            return null;
        }

        if ($shift->isBehaveAsNonWorkingDay) {
            return null;
        }

        if (empty($shift->endTime)) {
            return null;
        }

        $noOfDay = !empty($shift->noOfDay) ? $shift->noOfDay : 0;
        $workHours = !empty($shift->workHours) ? $shift->workHours : 0;

        if ($shift->hasMidnightCrossOver) {
            $dateObject = $dateObject->copy()->add(1, 'day');
        }

        $date = $dateObject->isoFormat('YYYY-MM-DD');
        $endDateTime = $date . " " . $shift->endTime;

        if ($leaveSummary['hasSecondHalfDayLeave']) {
            $outDateTime = Carbon::createFromFormat('Y-m-d H:i',  $endDateTime);
            if ($noOfDay == 0.5) { // if shift is half day
                return $endDateTime;
            } else {
                $outObj = $outDateTime->copy()->subMinutes(($workHours * 0.5));
                return $outObj->format('Y-m-d H:i');
            }
        } else if ($leaveSummary['hasOutShortLeave']) {
            $outDateTime = Carbon::createFromFormat('Y-m-d H:i',  $endDateTime);
            $outObj = $outDateTime->copy()->subMinutes($shortLeaveDuration);
            return $outObj->format('Y-m-d H:i');
        } else {
            return $endDateTime;
        }
    }

    protected function expectedWorkTime($shift, $leaveSummary)
    {
        if (is_null($shift)) {
            return 0;
        }
        if ($shift->isBehaveAsNonWorkingDay) {
            return 0;
        }

        $workHours = isset($shift->workHours) ? $shift->workHours : 0;        
        $leavePotionInMinutes = isset($leaveSummary['leavePotionInMinutes']) ? $leaveSummary['leavePotionInMinutes'] : 0;

        if ($leavePotionInMinutes == 0) {
            return $workHours;
        } else {
            return ($workHours - $leavePotionInMinutes);
        }
    }

    protected function getLeaveSummary($leaves, $shift)
    {
        $workHours = isset($shift->workHours) ? $shift->workHours : 0;
        $noOfDay = isset($shift->noOfDay) ? $shift->noOfDay : 0;
        $shortLeaveDuration = $this->getConfigValue('short_leave_duration');
        $leavePotionInMinutes = 0;
        $leavePotion = 0;

        $leaveDetails = [
            'leavePotionInMinutes' => 0,
            'leavePotion' => 0,
            'hasFullDayLeave' => false,
            'hasFirstHalfDayLeave' => false,
            'hasSecondHalfDayLeave' => false,
            'hasInShortLeave' => false,
            'hasOutShortLeave' => false
        ];

        foreach($leaves as $leave) {
            switch ($leave->leavePeriodType) {
                case 'FULL_DAY':
                    $leaveDetails['hasFullDayLeave'] = true;
                    $leavePotion += 1;
                    $leavePotionInMinutes += $workHours;
                    break;
                case 'FIRST_HALF_DAY':
                    $leaveDetails['hasFirstHalfDayLeave'] = true;
                    $leavePotion += 0.5;
                    $leavePotionInMinutes += ($noOfDay == 0.5) ? $workHours : ($workHours * 0.5);
                    break;
                case 'SECOND_HALF_DAY':
                    $leaveDetails['hasSecondHalfDayLeave'] = true;
                    $leavePotion += 0.5;
                    $leavePotionInMinutes += ($noOfDay == 0.5) ? $workHours : ($workHours * 0.5);
                    break;
                case 'IN_SHORT_LEAVE':
                    $leaveDetails['hasInShortLeave'] = true;
                    $leavePotionInMinutes += $shortLeaveDuration;
                    break;
                case 'OUT_SHORT_LEAVE':
                    $leaveDetails['hasOutShortLeave'] = true;
                    $leavePotionInMinutes += $shortLeaveDuration;
                    break;
            }
        }

        $leaveDetails['leavePotionInMinutes'] = $leavePotionInMinutes;
        $leaveDetails['leavePotion'] = $leavePotion;

        return $leaveDetails;
    }

    protected function isNoPay($shift, $leaveSummary, $actualIn = null, $actualOut = null)
    {
        if (!is_null($actualIn) || !is_null($actualOut)) {
            return false;
        }

        if (!is_null($shift)) {

            if ($shift->isBehaveAsNonWorkingDay) {
                //Need to clarify with BA madawa when its a noPay true if Days are 1 Day or 0.5Day and actualIn and ActualOut both missing
                return false;
            }  
        }

        return $this->isExpectedToPresent($shift, $leaveSummary);
    }

    /**
     * return positive value if late
     */
    public function lateIn($expectedIn, $actualIn)
    {
        if (is_null($expectedIn) || is_null($actualIn)) {
            return 0;
        }

        $expectedInObj = Carbon::parse($expectedIn);
        $actualInObj = Carbon::parse($actualIn);

        return $expectedInObj->diffInMinutes($actualInObj, false);
    }

    /**
     * return positive value if late
     */
    public function lateInForFlexiShifts($shift, $actualIn, $attendanceRecord)
    {
        $attendanceRecord = (array) $attendanceRecord;
        if (is_null($shift)) {
            return 0;
        }

        if ($attendanceRecord['isFullDayLeave']) {
            return 0;
        }

        if ($shift->endTime == '00:00' && $shift->startTime == '00:00') {
            return 0;
        }

        $expectedIn = $attendanceRecord['date'].' '.$shift->endTime;
        $leaveSummary = ($attendanceRecord['isHalfDayLeave'] || $attendanceRecord['isShortLeave']) ? $this->processLeaveSummary($attendanceRecord['employeeId'], $attendanceRecord['date'], $shift) : [];

        if (!empty($leaveSummary) && $attendanceRecord['isHalfDayLeave']) {
            return ($leaveSummary['hasFirstHalfDayLeave']) ? 0 : $this->lateIn($expectedIn, $actualIn);
        }

        if (!empty($leaveSummary) && $attendanceRecord['isShortLeave']) {
            return ($leaveSummary['hasInShortLeave']) ? 0 : $this->lateIn($expectedIn, $actualIn);
        }

        return $this->lateIn($expectedIn, $actualIn);
    }


    /**
     * return positive value if late
     */
    public function earlyOutForFlexiShifts($expectedWorkTime, $workTime)
    {
        $expectedWorkTime = (int) $expectedWorkTime;
        $workTime = (int) $workTime;

        if ($expectedWorkTime > $workTime) {
            $earlyOut = $expectedWorkTime - $workTime;
            return $earlyOut;
        }
        return 0;
    }

    /**
     * return positive value if early out
     */
    public function earlyOut($expectedOut, $actualOut)
    {
        if (is_null($expectedOut) || is_null($actualOut)) {
            return 0;
        }

        $expectedOutObj = Carbon::parse($expectedOut);
        $actualOutObj = Carbon::parse($actualOut);

        return $actualOutObj->diffInMinutes($expectedOutObj, false);
    }

    protected function workTimeInMinitues($actualOut, $actualIn)
    {
        if (is_null($actualIn) || is_null($actualOut)) {
            return 0;
        }

        $actualInObj = Carbon::parse($actualIn);
        $actualOutObj = Carbon::parse($actualOut);

        return $actualInObj->diffInMinutes($actualOutObj);
    }


    public function checAttendanceRecordIsLocked($employeeId, $dateArray) {

        $lockRecordsCount = 0;

        foreach ($dateArray as $key => $date) {
            //get employee wise attendance summary record
            $summaryRecord = DB::table('attendance_summary')->where('employeeId', $employeeId)->where('date', $date)->where('isLocked', true)->first();

            if (!empty($summaryRecord)) { // check if there is a summery record exsist for this particular date for this employee
                $lockRecordsCount ++;
            }
        }

        if ($lockRecordsCount > 0) {
            return true;
        }

        return false;
    }

    public function calculateWorkTimeAccordingToShiftPeriod ($summaryId, $employeeId, $shiftDate, $expectedIn, $expectedOut) 
    {
        $preShiftWorkTime = 0;
        $withinShiftWorkTime = 0; 
        $postShiftWorkTime = 0;

        $relatedAttendanceRecords = DB::table('attendance')->where('summaryId', $summaryId)->where('employeeId', $employeeId)->where('date', $shiftDate)->orderBy("in", "desc")->get();

        if (!empty($relatedAttendanceRecords) && !is_null($expectedIn) && !is_null($expectedOut)) {
            $expectedInObj = Carbon::parse($expectedIn);
            $expectedOutObj = Carbon::parse($expectedOut);

            foreach ($relatedAttendanceRecords as $key => $attendanceRecord) {

                $attendanceRecord = (array) $attendanceRecord;

                $attendanceInObj = Carbon::parse($attendanceRecord['in']);
                $attendanceOutObj = Carbon::parse($attendanceRecord['out']);

                //check whether attendance both in and out is less than expected in time
                if ($attendanceInObj->lt($expectedInObj) && $attendanceOutObj->lte($expectedInObj)) {
                    //calculate attendance record relate preshift worktime
                    $attendanceRealteActualPreShiftWorkTime = 0;
                    $atttendanceRelateFullWorkTime = $this->workTimeInMinitues($attendanceRecord['out'],$attendanceRecord['in']);

                    //get attendance relate breake record within attendaceInTime And attendaceOutTime
			        $preShiftBreakeTime = $this->calculateTotalBreakeTimeWithinGivenPeriod($attendanceRecord['id'], $attendanceRecord['in'], $attendanceRecord['out']);
                    
                    $attendanceRealteActualPreShiftWorkTime = $atttendanceRelateFullWorkTime - $preShiftBreakeTime;
                    $preShiftWorkTime +=  $attendanceRealteActualPreShiftWorkTime;
                    continue;

                }

                //check whether attendance in time less than expected in and attendance out is greater than expected in and attendance out is less than expected out
                if ($attendanceInObj->lt($expectedInObj) && $attendanceOutObj->gt($expectedInObj) && $attendanceOutObj->lte($expectedOutObj)) {

                    //calculate attendance record relate preshift worktime
                    $attendanceRealteActualPreShiftWorkTime = 0;
                    $atttendanceRelateFullWorkTime = $this->workTimeInMinitues($expectedIn, $attendanceRecord['in']);

                    //get attendance relate breake record within attendaceInTime And expectedInTime
                    $preShiftBreakeTime = $this->calculateTotalBreakeTimeWithinGivenPeriod($attendanceRecord['id'], $attendanceRecord['in'], $expectedIn);
                    
                    $attendanceRealteActualPreShiftWorkTime = $atttendanceRelateFullWorkTime - $preShiftBreakeTime;
                    $preShiftWorkTime +=  $attendanceRealteActualPreShiftWorkTime;


                    //calculate attendance record relate within shift worktime
                    $attendanceRealteActualWithinShiftWorkTime = 0;
			        $atttendanceRelateFullWithinShiftWorkTime = $this->workTimeInMinitues($attendanceRecord['out'], $expectedIn);

                    //get attendance relate breake record within expectedInTime and attendanceOutTime
                    $withinShiftBreakeTime = $this->calculateTotalBreakeTimeWithinGivenPeriod($attendanceRecord['id'], $expectedIn, $attendanceRecord['out']);
                    $attendanceRealteActualWithinShiftWorkTime = $atttendanceRelateFullWithinShiftWorkTime - $withinShiftBreakeTime;

                    $withinShiftWorkTime += $attendanceRealteActualWithinShiftWorkTime;
                    continue;

                }

                //check whether attendance both in and out is within expected in and expected out time
                if ($attendanceInObj->gte($expectedInObj) && $attendanceOutObj->lte($expectedOutObj)) {

                    //calculate attendance record relate within shift worktime
                    $attendanceRealteActualWithinShiftWorkTime = 0;
			        $atttendanceRelateFullWithinShiftWorkTime = $this->workTimeInMinitues($attendanceRecord['out'], $attendanceRecord['in']);

                    //get attendance relate breake record within attendanceInTime and attendanceOutTime
                    $withinShiftBreakeTime = $this->calculateTotalBreakeTimeWithinGivenPeriod($attendanceRecord['id'], $attendanceRecord['in'], $attendanceRecord['out']);
                    $attendanceRealteActualWithinShiftWorkTime = $atttendanceRelateFullWithinShiftWorkTime - $withinShiftBreakeTime;

                    $withinShiftWorkTime += $attendanceRealteActualWithinShiftWorkTime;
                    continue;
                }

                //check whether attendance in time greater than expected in and attendance out is greater than expected out
                if ($attendanceInObj->gte($expectedInObj) && $attendanceInObj->lt($expectedOutObj)  && $attendanceOutObj->gt($expectedOutObj)) {

                    //calculate attendance record relate within shift worktime
                    $attendanceRealteActualWithinShiftWorkTime = 0;
			        $atttendanceRelateFullWithinShiftWorkTime = $this->workTimeInMinitues($expectedOut, $attendanceRecord['in']);

                    //get attendance relate breake record within attendanceInTime and attendanceOutTime
                    $withinShiftBreakeTime = $this->calculateTotalBreakeTimeWithinGivenPeriod($attendanceRecord['id'], $attendanceRecord['in'], $expectedOut);
                    $attendanceRealteActualWithinShiftWorkTime = $atttendanceRelateFullWithinShiftWorkTime - $withinShiftBreakeTime;

                    $withinShiftWorkTime += $attendanceRealteActualWithinShiftWorkTime;


                    //calculate attendance record relate postshift worktime
                    $attendanceRealteActualPostShiftWorkTime = 0;
			        $atttendanceRelateFullPostShiftWorkTime = $this->workTimeInMinitues($attendanceRecord['out'], $expectedOut);

                    //get attendance relate breake record within expectedOutTime And attendaceOutTime
                    $postShiftBreakTime = $this->calculateTotalBreakeTimeWithinGivenPeriod($attendanceRecord['id'], $expectedOut, $attendanceRecord['out']);
                    $attendanceRealteActualPostShiftWorkTime = $atttendanceRelateFullPostShiftWorkTime - $postShiftBreakTime;


                    $postShiftWorkTime += $attendanceRealteActualPostShiftWorkTime;
                    continue;
                }

                //check whether attendance both in and out is greater than expected Out time
                if ($attendanceInObj->gte($expectedOutObj) && $attendanceOutObj->gt($expectedOutObj)) {

                    //calculate attendance record relate postshift worktime
                    $attendanceRealteActualPostShiftWorkTime = 0;
			        $atttendanceRelateFullPostShiftWorkTime = $this->workTimeInMinitues($attendanceRecord['out'], $attendanceRecord['in']);

                    //get attendance relate breake record within expectedOutTime And attendaceOutTime
                    $postShiftBreakTime = $this->calculateTotalBreakeTimeWithinGivenPeriod($attendanceRecord['id'], $attendanceRecord['in'], $attendanceRecord['out']);
                    $attendanceRealteActualPostShiftWorkTime = $atttendanceRelateFullPostShiftWorkTime - $postShiftBreakTime;


                    $postShiftWorkTime += $attendanceRealteActualPostShiftWorkTime;
                    continue;

                }

                //check whether attendance in less than expected in time and attendace out time is greater than expected out
                if ($attendanceInObj->lt($expectedInObj) && $attendanceOutObj->gt($expectedOutObj)) {

                    //calculate attendance record relate preshift worktime
                    $attendanceRealteActualPreShiftWorkTime = 0;
                    $atttendanceRelateFullWorkTime = $this->workTimeInMinitues($expectedIn, $attendanceRecord['in']);

                    //get attendance relate breake record within attendaceInTime And attendaceOutTime
			        $preShiftBreakeTime = $this->calculateTotalBreakeTimeWithinGivenPeriod($attendanceRecord['id'], $attendanceRecord['in'], $expectedIn);
                    
                    $attendanceRealteActualPreShiftWorkTime = $atttendanceRelateFullWorkTime - $preShiftBreakeTime;
                    $preShiftWorkTime +=  $attendanceRealteActualPreShiftWorkTime;
                    


                    //calculate attendance record relate within shift worktime
                    $attendanceRealteActualWithinShiftWorkTime = 0;
                    $atttendanceRelateFullWithinShiftWorkTime = $this->workTimeInMinitues($expectedOut, $expectedIn);

                    //get attendance relate breake record within attendanceInTime and attendanceOutTime
                    $withinShiftBreakeTime = $this->calculateTotalBreakeTimeWithinGivenPeriod($attendanceRecord['id'], $expectedIn, $expectedOut);
                    $attendanceRealteActualWithinShiftWorkTime = $atttendanceRelateFullWithinShiftWorkTime - $withinShiftBreakeTime;

                    $withinShiftWorkTime += $attendanceRealteActualWithinShiftWorkTime;


                    //calculate attendance record relate postshift worktime
                    $attendanceRealteActualPostShiftWorkTime = 0;
                    $atttendanceRelateFullPostShiftWorkTime = $this->workTimeInMinitues($attendanceRecord['out'], $expectedOut);

                    //get attendance relate breake record within expectedOutTime And attendaceOutTime
                    $postShiftBreakTime = $this->calculateTotalBreakeTimeWithinGivenPeriod($attendanceRecord['id'], $expectedOut, $attendanceRecord['out']);
                    $attendanceRealteActualPostShiftWorkTime = $atttendanceRelateFullPostShiftWorkTime - $postShiftBreakTime;

                    $postShiftWorkTime += $attendanceRealteActualPostShiftWorkTime;
                    continue;
                }
            }
        }

        $dataSet = [
            'preShiftWorkTime' => $preShiftWorkTime,
            'withinShiftWorkTime' => $withinShiftWorkTime,
            'postShiftWorkTime' =>$postShiftWorkTime
        ];

        return $dataSet;
    }


    public function calculateWorkTimeAccordingToOutDate($summaryId, $employeeId, $shiftDate, $expectedOut) 
    {
        $preShiftWorkTime = 0;
        $withinShiftWorkTime = 0; 
        $postShiftWorkTime = 0;

        $relatedAttendanceRecords = DB::table('attendance')->where('summaryId', $summaryId)->where('employeeId', $employeeId)->where('date', $shiftDate)->orderBy("in", "desc")->get();

        $expectedOutObj = Carbon::parse($expectedOut);

        if (!empty($relatedAttendanceRecords)) {
            foreach ($relatedAttendanceRecords as $key => $attendanceRecord) {

                $attendanceRecord = (array) $attendanceRecord;

                $attendanceInObj = Carbon::parse($attendanceRecord['in']);
                $attendanceOutObj = Carbon::parse($attendanceRecord['out']);



                //check whether attendance in time less than expected out and attendance out is greater than expected out
                if ($attendanceInObj->lt($expectedOutObj) && $attendanceOutObj->gt($expectedOutObj)) {

                    //calculate attendance record relate postshift worktime
                    $attendanceRealteActualPostShiftWorkTime = 0;
			        $atttendanceRelateFullPostShiftWorkTime = $this->workTimeInMinitues($attendanceRecord['out'], $expectedOut);

                    //get attendance relate breake record within expectedOutTime And attendaceOutTime
                    $postShiftBreakTime = $this->calculateTotalBreakeTimeWithinGivenPeriod($attendanceRecord['id'], $expectedOut, $attendanceRecord['out']);
                    $attendanceRealteActualPostShiftWorkTime = $atttendanceRelateFullPostShiftWorkTime - $postShiftBreakTime;


                    $postShiftWorkTime += $attendanceRealteActualPostShiftWorkTime;
                    continue;
                }

                //check whether attendance both in and out is greater than expected Out time
                if ($attendanceInObj->gte($expectedOutObj) && $attendanceOutObj->gt($expectedOutObj)) {

                    //calculate attendance record relate postshift worktime
                    $attendanceRealteActualPostShiftWorkTime = 0;
			        $atttendanceRelateFullPostShiftWorkTime = $this->workTimeInMinitues($attendanceRecord['out'], $attendanceRecord['in']);

                    //get attendance relate breake record within expectedOutTime And attendaceOutTime
                    $postShiftBreakTime = $this->calculateTotalBreakeTimeWithinGivenPeriod($attendanceRecord['id'], $attendanceRecord['in'], $attendanceRecord['out']);
                    $attendanceRealteActualPostShiftWorkTime = $atttendanceRelateFullPostShiftWorkTime - $postShiftBreakTime;


                    $postShiftWorkTime += $attendanceRealteActualPostShiftWorkTime;
                    continue;

                }
            }
        }

        $dataSet = [
            'preShiftWorkTime' => $preShiftWorkTime,
            'withinShiftWorkTime' => $withinShiftWorkTime,
            'postShiftWorkTime' =>$postShiftWorkTime
        ];

        return $dataSet;
    }


    public function calculateTotalBreakTime ($summaryId, $employeeId, $shiftDate) 
    {
       
        $totalBreakTime= 0;
        $relatedAttendanceRecords = DB::table('attendance')->where('summaryId', $summaryId)->where('employeeId', $employeeId)->where('date', $shiftDate)->orderBy("in", "desc")->get();

        if (!empty($relatedAttendanceRecords)) {
            foreach ($relatedAttendanceRecords as $key => $attendanceRecord) {
                $attendanceRecord = (array)($attendanceRecord);
                $totalBreaks = DB::table('break')->where('attendanceId', $attendanceRecord['id'])->selectRaw('(sum(break.diff)) as totalDiff')->first();
                $totalBreakTime += $totalBreaks->totalDiff;
            }
        }

        return $totalBreakTime;
    }

    protected function calculateTotalBreakeTimeWithinGivenPeriod($attendanceId, $attendanceIn, $attendanceOut) 
    {
        $totalBreakeTime = 0;
        $attendanceInObj = Carbon::parse($attendanceIn);
        $attendanceOutObj = Carbon::parse($attendanceOut);
       
        $relatedBreakeRecordsCollection = DB::table('break')
            ->where('attendanceId', $attendanceId)->get();

        if (!empty($relatedBreakeRecordsCollection)) {
            foreach ($relatedBreakeRecordsCollection as $key => $break) {
                $break = (array) $break;
                $breakePunchedInObj = Carbon::parse($break['in']);
                $breakePunchedOutObj = Carbon::parse($break['out']);

                //check break in and break out is within given date range
                if ($breakePunchedInObj->gte($attendanceInObj) && $breakePunchedOutObj->lte($attendanceOutObj)) {
                    $totalBreakeTime += $break['diff'];
                    continue;
                }

                //check break in is within given range and break out is greater than attendance out
                if ($breakePunchedInObj->lt($attendanceOutObj) && $breakePunchedOutObj->gt($attendanceOutObj)) {
                    $breakTime = $breakePunchedInObj->diffInMinutes($attendanceOutObj);

                    $totalBreakeTime += $breakTime; 
                    continue;
                }

                //check break in is less than attendance in and break out is withn date rangr
                if ($breakePunchedInObj->lt($attendanceInObj) && $breakePunchedOutObj->gt($attendanceInObj) && $breakePunchedOutObj->lte($breakePunchedOutObj)) {
                    $breakTime = $attendanceInObj->diffInMinutes($breakePunchedOutObj);

                    $totalBreakeTime += $breakTime; 
                    continue;
                }
            }
        }

        return $totalBreakeTime;
    }

    /**
     * calculate ot according to the attendance summary details
     */
    public function calculateAttendanceRelatedOTDetails($summaryId, $relatedAttendanceSummary)
    {
        try {
            $relatedAttendanceSummary = (array) $relatedAttendanceSummary;
            $dayTypeId = (!empty($relatedAttendanceSummary['dayTypeId'])) ? $relatedAttendanceSummary['dayTypeId'] : null;
            $shiftId = (!empty($relatedAttendanceSummary['shiftId'])) ? $relatedAttendanceSummary['shiftId'] : null;

            if (is_null($shiftId)) {
                return false;
            }

            //get workshift Data
            $relatedShift = DB::table('workShifts')
            ->join('workShiftDayType', 'workShiftDayType.workShiftId', '=', 'workShifts.id')
            ->where('workShifts.id', $shiftId)
                ->where('workShiftDayType.dayTypeId', $dayTypeId)
                ->first([
                    'workShiftDayType.isOTEnabled',
                    'workShiftDayType.inOvertime',
                    'workShiftDayType.outOvertime',
                    'workShiftDayType.deductLateFromOvertime',
                    'workShiftDayType.minimumOT',
                    'workShiftDayType.roundOffMethod',
                    'workShiftDayType.roundOffToNearest',
                    'workShiftDayType.breakTime'
                ]);

            $relatedEmployee = DB::table('employee')->where('id', $relatedAttendanceSummary['employeeId'])->first();
            $relatedEmployee = (array) $relatedEmployee;
            $isEmployeeOTAllowed = (isset($relatedEmployee['isOTAllowed']) && $relatedEmployee['isOTAllowed'] === 1) ? true : false;

            $isCalculateOt = $relatedShift->isOTEnabled;
            $isInOt = $relatedShift->inOvertime;
            $isOutOT = $relatedShift->outOvertime;
            $calculationAlowTime = 0;
            $minimumOtTime = $relatedShift->minimumOT;
            $calculationIgnoreTime = 0;

            //calculate the worked time that use for OT calculations
            if (!$isInOt && $isOutOT) {
                $calculationIgnoreTime = $relatedAttendanceSummary['preShiftWorkTime'];
                $calculationAlowTime = $relatedAttendanceSummary['withinShiftWorkTime'] + $relatedAttendanceSummary['postShiftWorkTime'];
            } else if (!$isOutOT && $isInOt) {
                $calculationIgnoreTime = $relatedAttendanceSummary['postShiftWorkTime'];
                $calculationAlowTime = $relatedAttendanceSummary['withinShiftWorkTime'] + $relatedAttendanceSummary['preShiftWorkTime'];
            } else if ($isOutOT && $isInOt) {
                $calculationAlowTime = $relatedAttendanceSummary['preShiftWorkTime'] + $relatedAttendanceSummary['withinShiftWorkTime'] + $relatedAttendanceSummary['postShiftWorkTime'];
            } else if (!$isOutOT && !$isInOt) {
                $calculationIgnoreTime = $relatedAttendanceSummary['postShiftWorkTime'] + $relatedAttendanceSummary['preShiftWorkTime'];
                $calculationAlowTime = $relatedAttendanceSummary['withinShiftWorkTime'];
            }


            //check whether shift configurations contain pre define fixed break
            if (!empty($relatedShift->breakTime) && $calculationAlowTime > 0) {
                $calculationAlowTime = ($calculationAlowTime > (float) $relatedShift->breakTime) ? $calculationAlowTime - (float) $relatedShift->breakTime : 0;
            }

            $balance = $calculationAlowTime;
            $calculatedTime = 0;
            $totalOtTime = 0;
            $isHaveOtBasedPayTypes = false;
            $summaryPayTypeDetailArray = [];
            $calculatedOtPayTypes = [];

            //get realted pay configuration record
            $relatedPayConfigRecord = DB::table('workShiftPayConfiguration')
            ->where('workShiftPayConfiguration.workShiftId', '=', $shiftId)
                ->where('workShiftPayConfiguration.workCalendarDayTypeId', '=', $dayTypeId)->first(['payConfigType']);

            //get related pay threshold
            $relatedPayThresholdsSet = DB::table('workShiftPayConfiguration')
            ->leftJoin('workShiftPayConfigurationThreshold', 'workShiftPayConfigurationThreshold.workShiftPayConfigurationId', '=', 'workShiftPayConfiguration.id')
            ->leftJoin('payType', 'payType.id', '=', 'workShiftPayConfigurationThreshold.payTypeId')
            ->where('workShiftPayConfiguration.workShiftId', '=', $shiftId)
                ->where('workShiftPayConfiguration.workCalendarDayTypeId', '=', $dayTypeId)->orderBy('workShiftPayConfigurationThreshold.thresholdSequence', 'asc')->get([
                    'workShiftPayConfigurationThreshold.id',
                    'workShiftPayConfigurationThreshold.hoursPerDay',
                    'workShiftPayConfigurationThreshold.payTypeId',
                    'workShiftPayConfigurationThreshold.thresholdSequence',
                    'workShiftPayConfigurationThreshold.workShiftPayConfigurationId',
                    'workShiftPayConfigurationThreshold.validTime',
                    'payType.type',
                ]);

            // check whether related work shift allow to calculate ot
            if ($isCalculateOt && $isEmployeeOTAllowed) {
                if (!is_null($relatedPayConfigRecord) && $relatedPayConfigRecord->payConfigType == 'HOUR_BASE' && sizeof($relatedPayThresholdsSet) > 0) {

                    for ($i = 0; $i < sizeof($relatedPayThresholdsSet); $i++) {
                        $index = $i + 1;

                        $relateThresholdData = (isset($relatedPayThresholdsSet[$index - 1])) ? $relatedPayThresholdsSet[$index - 1] : null;
                        $relateThresholdData = (!is_null($relateThresholdData)) ? (array) $relateThresholdData : null;

                        //get Next Threshold
                        $nextIndex = $index + 1;
                        $nextThresholdData = (isset($relatedPayThresholdsSet[$nextIndex - 1])) ? $relatedPayThresholdsSet[$nextIndex - 1] : null;
                        $nextThresholdData = (!is_null($nextThresholdData)) ? (array) $nextThresholdData : null;

                        if (!empty($relateThresholdData)) {

                            //get Relate pay type data 
                            $payKey = 'payType' . $relateThresholdData['payTypeId'];
                            $thresholdAllocateTime = $relateThresholdData['hoursPerDay'] * 60;
                            $actualPayTypeWorkTime = 0;

                            //check whether pay type is general or overtime based
                            if ($relateThresholdData['type'] == 'GENERAL') {
                                if ($balance <= $thresholdAllocateTime) {
                                    $actualPayTypeWorkTime = $balance;
                                    $balance = 0;
                                } elseif ($thresholdAllocateTime < $balance) {
                                    $actualPayTypeWorkTime = $thresholdAllocateTime;
                                    $balance -= $actualPayTypeWorkTime;
                                }
                            } elseif ($relateThresholdData['type'] == 'OVERTIME') {

                                if ($relateThresholdData['thresholdSequence'] == 1 && $relateThresholdData['hoursPerDay'] > 0) {
                                    if ($balance <= $thresholdAllocateTime) {
                                        $actualPayTypeWorkTime = $balance;
                                        $balance = 0;
                                    } elseif ($thresholdAllocateTime < $balance) {
                                        $actualPayTypeWorkTime = $thresholdAllocateTime;
                                        $balance -= $actualPayTypeWorkTime;
                                    }

                                    $summaryPayTypeDetailArray['payType1'] = [
                                        'summaryId' => $summaryId,
                                        'payTypeId' => 1,
                                        'workedTime' => $actualPayTypeWorkTime,
                                        'thresholdSequence' => null
                                    ];
                                }

                                //get the available hour slots for current pay type
                                if (!is_null($nextThresholdData)) {
                                    $thresholdAllocateTime = $nextThresholdData['hoursPerDay'] * 60;
                                } else {
                                    $thresholdAllocateTime = $balance;
                                }

                                $isHaveOtBasedPayTypes = true;
                                if ($balance <= $thresholdAllocateTime) {
                                    $actualPayTypeWorkTime = $balance;
                                    $balance = 0;
                                } elseif ($thresholdAllocateTime < $balance) {
                                    $actualPayTypeWorkTime = $thresholdAllocateTime;
                                    $balance -= $actualPayTypeWorkTime;
                                }

                                if (!empty($relatedShift->roundOffMethod) && $relatedShift->roundOffMethod == 'ROUND_UP') {

                                    if ($actualPayTypeWorkTime != 0 && !empty($relatedShift->roundOffToNearest)) {
                                        //roundup the actual pay type work time
                                        $actualPayTypeWorkTime = $this->roundToTheNearestAnything($actualPayTypeWorkTime, $relatedShift->roundOffToNearest);
                                    }
                                }

                                $totalOtTime += $actualPayTypeWorkTime;
                                $calculatedOtPayTypes[] = $payKey;
                            }

                            $summaryPayTypeDetailArray[$payKey] = [
                                'summaryId' => $summaryId,
                                'payTypeId' => $relateThresholdData['payTypeId'],
                                'workedTime' => $actualPayTypeWorkTime,
                                'thresholdSequence' => $relateThresholdData['thresholdSequence']
                            ];
                        } else {
                            //if there is no more any related pay thresholds, worked time automatically add to regular pay type
                            $payKey = 'payType1';
                            if (isset($summaryPayTypeDetailArray[$payKey])) {
                                $summaryPayTypeDetailArray[$payKey]['workedTime'] += $balance;
                                $balance = 0;
                            } else {
                                $summaryPayTypeDetailArray[$payKey] = [
                                    'summaryId' => $summaryId,
                                    'payTypeId' => 1,
                                    'workedTime' => $balance,
                                    'thresholdSequence' => null
                                ];
                                $balance = 0;
                            }
                        }
                    }
                } elseif (!is_null($relatedPayConfigRecord) && $relatedPayConfigRecord->payConfigType == 'TIME_BASE' && sizeof($relatedPayThresholdsSet) > 0) {
                    $fromDateTime = $relatedAttendanceSummary['expectedIn'];
                    $toDateTime = null;
                    $calculatedOtPayTypeCount = 0;

                    for ($i = 0; $i < sizeof($relatedPayThresholdsSet); $i++) {
                        $index = $i + 1;

                        $relateThresholdData = (isset($relatedPayThresholdsSet[$index - 1])) ? $relatedPayThresholdsSet[$index - 1] : null;
                        $relateThresholdData = (!is_null($relateThresholdData)) ? (array) $relateThresholdData : null;

                        //get Next Threshold
                        $nextIndex = $index + 1;
                        $nextThresholdData = (isset($relatedPayThresholdsSet[$nextIndex - 1])) ? $relatedPayThresholdsSet[$nextIndex - 1] : null;
                        $nextThresholdData = (!is_null($nextThresholdData)) ? (array) $nextThresholdData : null;
                        $nextThresholdValidTime = (!empty($nextThresholdData['validTime'])) ? $nextThresholdData['validTime'] : null;

                        if (!empty($relateThresholdData)) {

                            //get Relate pay type data 
                            $payKey = 'payType' . $relateThresholdData['payTypeId'];
                            $thresholdAllocateTime = $relateThresholdData['hoursPerDay'] * 60;
                            $actualPayTypeWorkTime = 0;


                            //check whether pay type is general or overtime based
                            if ($relateThresholdData['type'] == 'GENERAL') {
                                if ($balance <= $thresholdAllocateTime) {
                                    $actualPayTypeWorkTime = $balance;
                                    $balance = 0;
                                } elseif ($thresholdAllocateTime < $balance) {
                                    $actualPayTypeWorkTime = $thresholdAllocateTime;
                                    $balance -= $actualPayTypeWorkTime;
                                }
                            } elseif ($relateThresholdData['type'] == 'OVERTIME') {

                                if ($relateThresholdData['thresholdSequence'] == 1) {

                                    $toDateTime = $relatedAttendanceSummary['date'] . ' ' . $relateThresholdData['validTime'] . '.000';
                                    $timeSlotWorkingData = $this->calculateWorkTimeAccordingToShiftPeriod($summaryId, $relatedAttendanceSummary['employeeId'], $relatedAttendanceSummary['date'], $fromDateTime, $toDateTime);
                                    $workTime = $timeSlotWorkingData['withinShiftWorkTime'];

                                    if ($balance <= $workTime) {
                                        $actualPayTypeWorkTime = $balance;
                                        $balance = 0;
                                    } elseif ($workTime < $balance) {
                                        $actualPayTypeWorkTime = $workTime;
                                        $balance -= $actualPayTypeWorkTime;
                                    }

                                    $summaryPayTypeDetailArray['payType1'] = [
                                        'summaryId' => $summaryId,
                                        'payTypeId' => 1,
                                        'workedTime' => $actualPayTypeWorkTime,
                                        'thresholdSequence' => null
                                    ];
                                }

                                $fromDateTime = $relatedAttendanceSummary['date'] . ' ' . $relateThresholdData['validTime'] . '.000';
                                $preShiftWorkTimeCount = ($calculatedOtPayTypeCount == 0 && $isInOt) ? $relatedAttendanceSummary['preShiftWorkTime'] : 0;
                                if (!is_null($nextThresholdValidTime)) {
                                    $nextThresholdStartDateTime = $relatedAttendanceSummary['date'] . ' ' . $nextThresholdValidTime . '.000';
                                    $currentTimeSlotWorkigDetail =  $this->calculateWorkTimeAccordingToShiftPeriod($summaryId, $relatedAttendanceSummary['employeeId'], $relatedAttendanceSummary['date'], $fromDateTime, $nextThresholdStartDateTime);
                                    $thresholdAllocateTime = $currentTimeSlotWorkigDetail['withinShiftWorkTime'] + $preShiftWorkTimeCount;
                                } else {
                                    $currentTimeSlotWorkigDetail =  $this->calculateWorkTimeAccordingToOutDate($summaryId, $relatedAttendanceSummary['employeeId'], $relatedAttendanceSummary['date'], $fromDateTime);
                                    $thresholdAllocateTime = $currentTimeSlotWorkigDetail['postShiftWorkTime'] + $preShiftWorkTimeCount;
                                }

                                $isHaveOtBasedPayTypes = true;
                                if ($balance <= $thresholdAllocateTime) {
                                    $actualPayTypeWorkTime = $balance;
                                    $balance = 0;
                                } elseif ($thresholdAllocateTime < $balance) {
                                    $actualPayTypeWorkTime = $thresholdAllocateTime;
                                    $balance -= $actualPayTypeWorkTime;
                                }

                                if (!empty($relatedShift->roundOffMethod) && $relatedShift->roundOffMethod == 'ROUND_UP') {

                                    if ($actualPayTypeWorkTime != 0 && !empty($relatedShift->roundOffToNearest)) {
                                        //roundup the actual pay type work time
                                        $actualPayTypeWorkTime = $this->roundToTheNearestAnything($actualPayTypeWorkTime, $relatedShift->roundOffToNearest);
                                    }
                                }

                                $totalOtTime += $actualPayTypeWorkTime;
                                $calculatedOtPayTypes[] = $payKey;
                                $calculatedOtPayTypeCount++;
                            }

                            $summaryPayTypeDetailArray[$payKey] = [
                                'summaryId' => $summaryId,
                                'payTypeId' => $relateThresholdData['payTypeId'],
                                'workedTime' => $actualPayTypeWorkTime,
                                'thresholdSequence' => $relateThresholdData['thresholdSequence']
                            ];
                        } else {
                            //if there is no more any related pay thresholds, worked time automatically add to regular pay type
                            $payKey = 'payType1';
                            if (isset($summaryPayTypeDetailArray[$payKey])) {
                                $summaryPayTypeDetailArray[$payKey]['workedTime'] += $balance;
                                $balance = 0;
                            } else {
                                $summaryPayTypeDetailArray[$payKey] = [
                                    'summaryId' => $summaryId,
                                    'payTypeId' => 1,
                                    'workedTime' => $balance,
                                    'thresholdSequence' => null
                                ];
                                $balance = 0;
                            }
                        }
                    }
                }
            } else {
                //if relate workshift is not allow to calculate ot worked time automatically add to regular pay type
                $payKey = 'payType1';
                $summaryPayTypeDetailArray[$payKey] = [
                    'summaryId' => $summaryId,
                    'payTypeId' => 1,
                    'workedTime' => $calculationAlowTime,
                    'thresholdSequence' => null
                ];
            }

            if ($calculationIgnoreTime > 0) {
                //add ot calculation ignoring shift work time under the regular pay type 
                $payKey = 'payType1';
                if (isset($summaryPayTypeDetailArray[$payKey])) {
                    $summaryPayTypeDetailArray[$payKey]['workedTime'] += $calculationIgnoreTime;
                } else {
                    $summaryPayTypeDetailArray[$payKey] = [
                        'summaryId' => $summaryId,
                        'payTypeId' => 1,
                        'workedTime' => $calculationIgnoreTime,
                        'thresholdSequence' => null
                    ];
                }
            }

            //handle if total count of ot hours is less than minimum count of ot time situation
            if ($isHaveOtBasedPayTypes && ($minimumOtTime > $totalOtTime)) {
                //if total ot count less than the minimum ot count, auto matically total ot hours add under the regular pay type 
                $payKey = 'payType1';
                $newSummaryArray = [];
                foreach ($summaryPayTypeDetailArray as $keyName => $val) {
                    if (!in_array($keyName, $calculatedOtPayTypes)) {
                        $newSummaryArray[$keyName] = $val;
                    }
                }
                $summaryPayTypeDetailArray = [];
                $summaryPayTypeDetailArray = array_merge($summaryPayTypeDetailArray, $newSummaryArray);

                $payKey = 'payType1';
                if (isset($summaryPayTypeDetailArray[$payKey])) {
                    $summaryPayTypeDetailArray[$payKey]['workedTime'] += $totalOtTime;
                    $totalOtTime = 0;
                } else {
                    $summaryPayTypeDetailArray[$payKey] = [
                        'summaryId' => $summaryId,
                        'payTypeId' => 1,
                        'workedTime' => $totalOtTime,
                        'thresholdSequence' => null
                    ];
                    $totalOtTime = 0;
                }
            }

            //handle in late from scenario in ot calculations
            $inLate = (!empty($relatedAttendanceSummary['lateIn'])) ? $relatedAttendanceSummary['lateIn'] : 0;

            if ($inLate > 0 && $relatedShift->deductLateFromOvertime) {
                $balanceLate = $inLate;
                for ($k=0; $k < 4 ; $k++) { 
                    $thresoldNum = $k + 1;

                    if ($balanceLate == 0) {
                        break;
                    }
                    $thresoldRelatePayData = array_values(array_filter($summaryPayTypeDetailArray, function ($item) use ($thresoldNum) {
                        if ($item['thresholdSequence'] === $thresoldNum) {
                            return $item;
                        }
                    }));
                    
                    if (!empty($thresoldRelatePayData)) {
                        $payKey = 'payType'.$thresoldRelatePayData[0]['payTypeId'];
                        if ($thresoldRelatePayData[0]['workedTime'] >= $balanceLate) {
                            $summaryPayTypeDetailArray[$payKey]['workedTime'] = $thresoldRelatePayData[0]['workedTime'] - $balanceLate;
                            $balanceLate = 0;
                        } elseif ($thresoldRelatePayData[0]['workedTime'] < $balanceLate) {
                            $balanceLate = $balanceLate - $summaryPayTypeDetailArray[$payKey]['workedTime'];
                            $summaryPayTypeDetailArray[$payKey]['workedTime'] = 0;
                        }

                    }

  
                }
            }

            if (!empty($summaryPayTypeDetailArray)) {
                $summaryPayTypeDetailArray = array_values($summaryPayTypeDetailArray);

                //delete all previous summary related pay type details 
                $delete = DB::table('attendanceSummaryPayTypeDetail')->where('summaryId', $summaryId)->delete();

                foreach ($summaryPayTypeDetailArray as $payTypeDetailKey => $payTypeDetail) {
                    $payTypeDetail = (array) $payTypeDetail;
                    $savingData = [
                        'summaryId' => $payTypeDetail['summaryId'],
                        'payTypeId' => $payTypeDetail['payTypeId'],
                        'workedTime' => $payTypeDetail['workedTime']
                    ];

                    $attendanceSummaryPayTypeDetailId = DB::table('attendanceSummaryPayTypeDetail')
                    ->insertGetId($savingData);
                }
            }
        } catch (\Throwable $th) {
            error_log($th->getMessage());
            throw $th;
        }
    }


    private function roundToTheNearestAnything($value, $roundType)
    {
        $roundTo = 0;
        switch ($roundType) {
            case '5_MINUTES':
                $roundTo = 5;
                break;
            case '15_MINUTES':
                $roundTo = 15;
                break;
            case '30_MINUTES':
                $roundTo = 30;
                break;
            case '1_HOUR':
                $roundTo = 60;
                break;
            default:
                # code...
                break;
        }

        $mod = $value%$roundTo;
        return $value+($mod<($roundTo/2)?-$mod:$roundTo-$mod);
    }

    // private function deductInLateFromCalculatedOT($inLate, $summaryPayTypeDetailArray) 
    // {
    //     error_log(json_encode($summaryPayTypeDetailArray));

    //     $processedDataSet = [];

    //     foreach ($summaryPayTypeDetailArray as $key => $value) {
    //         error_log($value->payTypeId);
    //     }

    // }
}
