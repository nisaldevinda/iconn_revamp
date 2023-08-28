<?php

namespace App\Traits;

use App\Library\Model;
use App\Exceptions\Exception;
use Illuminate\Support\Facades\DB;
use DateTime;
use DateTimeZone;
use Carbon\Carbon;
use stdClass;
use App\Events\AttendanceDateDetailChangedEvent;
/**
 * AttendanceSync is trait for sync attendance from bio metric device to sysytem
 */
trait AttendanceSync
{

    use JobResponser;
    use EmployeeHelper;
    use ConfigHelper;
    use AttendanceProcess;

    /**
     * Sync attendance to the system
     * 
     */
    public function syncAttendanceToSystem($isForcePrcessed = false)
    {
        try {
            

            if (!$isForcePrcessed) {
                //get attendance dataset from backlog without previously faild atendance records
                $backlogDataset = DB::table('attendance_backup_log')->where('isProcessed', false)->get();
            }  else {
                //get all attendance dataset from backlog 
                $backlogDataset = DB::table('attendance_backup_log')->get();
            }

            $dataSet = collect($backlogDataset)->map(function($item) {
                $temp['id'] = $item->id;
                $temp['attendanceId'] = $item->attendanceId;
                $temp['type'] = $item->type;
                $temp['mode'] = $item->mode;
                $temp['recordTime'] = $item->recordTime;
                return $temp;
            });

            $attendanceDataChunks = $dataSet->chunk(500);

            foreach ($attendanceDataChunks as $key => $chunk) {
                $attendanceDataChunks[$key] = array_values($chunk->toArray());
            }

            foreach ($attendanceDataChunks as $chunkKey => $AttendanceChunk) {
                //categorized attendance record according to the attedenceId
                $groupedAttendanceList = [];
                $attendanceIds = [];
                $syncSuccessAttendanceLogIds = [];
                $syncFaildAttendanceLogIds = [];
                foreach ($AttendanceChunk as $key => $value) {

                    $attKey = 'Att-' .$value['attendanceId'];
                    $groupedAttendanceList[$attKey][] = $value;
                    array_push($attendanceIds, $value['attendanceId']);
                }

                //get related employee ids 
                $employees = DB::table('employee')->select('employee.id as employeeId','employee.attendanceId as attId')->whereIn('attendanceId', array_values(array_unique($attendanceIds)))->where('isDelete', false)->get();

                //process attendance according to the provided attendance details
                foreach ($groupedAttendanceList as $attKey => $attendanceRealteSet) {
                    //get related employee from attendanceid
                    $attIdArr = explode('Att-',$attKey);
                    $attendanceId = $attIdArr[1];
                    $empObj = null;

                    //get related employee from employee array
                    foreach ($employees as $empkey => $employee) {
                        if ($attendanceId == $employee->attId) {
                            $empObj = $employee;
                            break;
                            
                        }
                    }
                    $employeeId = is_null($empObj) ? null : $empObj->employeeId;
                    $relatedShiftDates = [];

                    foreach ($attendanceRealteSet as $attendanceKey => $attendanceRecord) {
                        $attendanceRecord['employeeId'] = $employeeId;
                        $syncRecord = $this->manageAttendanceSyncing($attendanceRecord);

                        if ($syncRecord['syncState']) {

                            array_push($syncSuccessAttendanceLogIds,$attendanceRecord['id']);
                            array_push($relatedShiftDates,$syncRecord['shiftDate']);
                        } else {
                            //update status
                            array_push($syncFaildAttendanceLogIds,$attendanceRecord['id']);
                        }
                    }

                    if (sizeof($relatedShiftDates) > 0) {
                        $dataSet = [
                            'employeeId' => $employeeId,
                            'dates' => $relatedShiftDates
                        ];
                        event(new AttendanceDateDetailChangedEvent($dataSet));
                    }

                }

                if (!empty($syncSuccessAttendanceLogIds)) {
                    //delete sucessfully sync record from attendance log table
                    $delete = $this->store->getFacade()::table('attendance_backup_log')
                    ->whereIn('id',$syncSuccessAttendanceLogIds)
                    ->delete();
                }

                if (!empty($syncFaildAttendanceLogIds)) {
                    //update the state of faild sync attendance record in attendance log table
                    $update = $this->store->getFacade()::table('attendance_backup_log')
                    ->whereIn('id',$syncFaildAttendanceLogIds)
                    ->update(['isProcessed' => true]);
                }
            }
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    /**
     * get shift data for the particular date and employee
     * 
     */
    private function getShiftDataByDateAndEmployee($dateObj, $employeeId)
    {
        $date = Carbon::parse($dateObj)->format('Y-m-d');

        // get calendars special days
        $specialDaysCollection = DB::table('workCalendarSpecialDays')
            ->whereIn('date', [$date])
            ->where('workCalendarDayTypeId', '!=', 1)
            ->get(['calendarId', 'workCalendarDayTypeId']);


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

        $timeZone = $this->getEmployeeTimeZoneForDate($employeeId, $date);

        $calendarDayTypeId = null;

        // to check whether holiday 
        $holiday = $specialDaysCollection->where('calendarId', $calendarId)->where('date', $date)->first();

        if (empty($holiday)) {
            $calendarDayTypeId = $this->getCalendarDayTypeId($calendarId, $dayName);
        } else { // if holiday
            $calendarDayTypeId = $holiday->workCalendarDayTypeId;
        }

        $shift = $this->getEmployeeWorkShift($employeeId, $dateObj, $calendarDayTypeId);

        $expectedIn = NULL;
        $expectedOut = NULL;

        if (!is_null($shift) && !$shift->isBehaveAsNonWorkingDay) {        
            // get leaves
            $leaves = $this->getEmployeeLeave($employeeId, $date);
    
            // get short leaves
            $shortLeaves = $this->getEmployeeShortLeave($employeeId, $date);
    
            if (sizeof($leaves) > 0 && sizeof($shortLeaves) > 0) {
                $leaves = array_merge($leaves, $shortLeaves);
            }
    
            if (sizeof($leaves) === 0 && !empty($shortLeaves)) {
                $leaves = $shortLeaves;
            }
    
            // get leave summary
            $leaveSummary = $this->getLeaveSummary($leaves, $shift);
    
            $expectedIn = $this->expectedIn($dateObj, $shift, $leaveSummary);
            $expectedOut = $this->expectedOut($dateObj, $shift, $leaveSummary);
        } 

        return [
            'shift' => $shift,
            'expectedIn' => $expectedIn,
            'expectedOut' => $expectedOut,
            'timeZone' => $timeZone
        ];
    }

    /**
     * get shift date for the particular attendace record
     * 
     */
    private function getShiftDateForAttendanceRecord($attendanceRecord, $employeeTimeZone)
    {
        $dateArr = explode(" ", $attendanceRecord['recordTime']);
        $dateObj = Carbon::parse($dateArr[0]);
        $date = $dateArr[0];
        $recordTimeObj = Carbon::parse($attendanceRecord['recordTime'], $employeeTimeZone);

        $recordTimeObjInUTC = $recordTimeObj->copy()->tz('UTC');
        $employeeId = $attendanceRecord['employeeId'];

        $currentShiftData = $this->getShiftDataByDateAndEmployee($dateObj, $employeeId);

        if (!is_null($currentShiftData['expectedIn']) && !is_null($currentShiftData['expectedOut'])) {

            $expectedInUTC = Carbon::parse($currentShiftData['expectedIn'], $currentShiftData['timeZone'])->copy()->tz('UTC');
            $expectedOutUTC = Carbon::parse($currentShiftData['expectedOut'], $currentShiftData['timeZone'])->copy()->tz('UTC');
    
            //check whether punched time is within the shift date time range
            if ($recordTimeObjInUTC->gte($expectedInUTC) && $recordTimeObjInUTC->lte($expectedOutUTC)) {
                return $date;
            }
    
            //check whether punched time is less than expected in
            if ($recordTimeObjInUTC->lt($expectedInUTC)) {
                $previousDate = Carbon::parse($date)->subDay()->format('Y-m-d');
                $previousDayShiftData = $this->getShiftDataByDateAndEmployee(Carbon::parse($previousDate), $employeeId);
                $shiftThreshold = config('app.shift_threshold');

                if (is_null($previousDayShiftData['expectedIn']) && is_null($previousDayShiftData['expectedOut'])) {
                    $previousDateEndingTimeInUTC = Carbon::parse($previousDate.' 23:59:00', $previousDayShiftData['timeZone'])->copy()->tz('UTC');
    
                    if ($recordTimeObjInUTC->gte($previousDateEndingTimeInUTC) ) {
                        return $date;
                    } else {
                        $previousDate;
                    }
                }

                $previousDayExpectedInUTC = Carbon::parse($previousDayShiftData['expectedIn'], $previousDayShiftData['timeZone'])->copy()->tz('UTC');
                $previousDayExpectedOutUTC = Carbon::parse($previousDayShiftData['expectedOut'], $previousDayShiftData['timeZone'])->copy()->tz('UTC');
    
                $previousDayExpectedOutWithThreshold = ($shiftThreshold == 0) ? $previousDayExpectedOutUTC->copy() :  $previousDayExpectedOutUTC->copy()->addMinutes($shiftThreshold);
           
                //if previous day havent shift return shift day as current date
                if (is_null($previousDayShiftData['shift'])) {
                    return $date;
                }
    
                //check whether previous day shift has midnight cross over
                if (!$previousDayShiftData['shift']->hasMidnightCrossOver) {
                    return $date;
                }
    
                //check punched time is within the previous day shift range
                if ($recordTimeObjInUTC->gte($previousDayExpectedInUTC) && $recordTimeObjInUTC->lte($previousDayExpectedOutWithThreshold)) {
                    return $previousDate;
                } 
            }
        }

        if (is_null($currentShiftData['expectedIn']) && is_null($currentShiftData['expectedOut'])) {

            $previousDate = Carbon::parse($date)->subDay()->format('Y-m-d');
            $previousDayShiftData = $this->getShiftDataByDateAndEmployee(Carbon::parse($previousDate), $employeeId);
            $shiftThreshold = config('app.shift_threshold');

            if (is_null($previousDayShiftData['expectedIn']) && is_null($previousDayShiftData['expectedOut'])) {
                $previousDateEndingTimeInUTC = Carbon::parse($previousDate.' 23:59:00', $previousDayShiftData['timeZone'])->copy()->tz('UTC');

                if ($recordTimeObjInUTC->gte($previousDateEndingTimeInUTC) ) {
                    return $date;
                } else {
                    $previousDate;
                }
            }

            $previousDayExpectedInUTC = Carbon::parse($previousDayShiftData['expectedIn'], $previousDayShiftData['timeZone'])->copy()->tz('UTC');
            $previousDayExpectedOutUTC = Carbon::parse($previousDayShiftData['expectedOut'], $previousDayShiftData['timeZone'])->copy()->tz('UTC');

            $previousDayExpectedOutWithThreshold = ($shiftThreshold == 0) ? $previousDayExpectedOutUTC->copy() :  $previousDayExpectedOutUTC->copy()->addMinutes($shiftThreshold);
        
            //if previous day havent shift return shift day as current date
            if (is_null($previousDayShiftData['shift'])) {
                return $date;
            }

            //check whether previous day shift has midnight cross over
            if (!$previousDayShiftData['shift']->hasMidnightCrossOver) {
                return $date;
            }

            //check punched time is within the previous day shift range
            if ($recordTimeObjInUTC->gte($previousDayExpectedInUTC) && $recordTimeObjInUTC->lte($previousDayExpectedOutWithThreshold)) {
                return $previousDate;
            } 
        }

        
        return $date;
    }

     /**
     * Get employee related timezone for particular date
     * 
     */
    private function getEmployeeTimeZoneForDate($employeeId, $date) {
        
        // get all locations
        $locationCollection = DB::table('location')->get(['id', 'timeZone']);

        // get company timezone
        $company = DB::table('company')->first('timeZone');

        $timeZone = $company->timeZone;

        // get employee current job
        $employeeJob = $this->getEmployeeJob($employeeId, $date, ['calendarId', 'locationId']);
        // check whether employee has a job, if job not exist ignore that employee
        if (!empty($employeeJob)) {
           // get location
           $locationId = empty($employeeJob->locationId) ? null : $employeeJob->locationId;

            // if location not exist get default company timezone
            $timeZone = empty($locationId) ? $company->timeZone : $locationCollection->firstWhere('id', $locationId)->timeZone;
        } else {
            return null;
        }

        return $timeZone;

    }

    /**
     * Sync records wise attendance to the system
     * 
     */
    private function manageAttendanceSyncing($attendanceRecord)
    {
        $attendanceRecord = (array) $attendanceRecord;
        $employeeId = $attendanceRecord['employeeId'];
        $company = DB::table('company')->first('timeZone');

        $attendanceDate = Carbon::parse($attendanceRecord['recordTime'])->format('Y-m-d');
        //get employee timeZone for current date
        $timeZone = $this->getEmployeeTimeZoneForDate($employeeId, $attendanceDate);

        if (is_null($timeZone)) {
            return [
                'syncState' => false
            ]; 
        }

        //get the appropriate shift date for process the attendance
        $selctedShiftDate = $this->getShiftDateForAttendanceRecord($attendanceRecord, $timeZone);

        if (!$employeeId) {
            return [
                'syncState' => false
            ]; 
        }

        //get summary record for particular date 
        $summarySaved = $this->getAttendanceSummaryForSync($employeeId, Carbon::parse($selctedShiftDate, $timeZone), $timeZone);

        $shiftDate = $summarySaved->date;

        $shiftName = null;
        $summaryData = new stdClass();
        $summaryData->date = $shiftDate;
        $summaryData->timeZone = $timeZone;
        $summaryData->employeeId = $employeeId;

        if (!is_null($summarySaved->shiftId)) {
            $shiftData = $this->store->getFacade()::table('workShifts')->where('id', $summarySaved->shiftId)->first();

            if (empty($shiftData->name)) {
                $workPattern = $this->getWorkPatternByShiftId($summarySaved->shiftId);
                $shiftName = isset($workPattern->name) ? $workPattern->name : null;
            }
        }

        $attendanceSaved = $this->store->getFacade()::table('attendance')->where('summaryId', $summarySaved->id)->where('employeeId', $employeeId)->where('date', $shiftDate)->orderBy("in", "desc")->first();

        $isNewAttendance = is_null($attendanceSaved);
        $isPunchedInOnly = !is_null($attendanceSaved) && (is_null($attendanceSaved->out) || $attendanceSaved->out == '0000-00-00 00:00:00');

        if ($isNewAttendance || !$isPunchedInOnly) {
            // punched in add
            $attendancePunchedDate = Carbon::parse($attendanceRecord['recordTime'], $timeZone);
            $attendancePunchedDateTimeUTC = $attendancePunchedDate->copy()->tz('UTC');

            $expectedTime = Carbon::parse($summarySaved->expectedIn, $timeZone);

            $lateCountInSec = $this->timeDiffSeconds($attendancePunchedDate, $expectedTime, false);
            $lateCountInMin = (int) ($lateCountInSec / 60);

            $newAttendance = new stdClass();
            $newAttendance->date = $shiftDate;
            $newAttendance->in = $attendancePunchedDate->format("Y-m-d H:i:s e");
            $newAttendance->inUTC = $attendancePunchedDateTimeUTC->format("Y-m-d H:i:s e");
            $newAttendance->typeId = 0;
            $newAttendance->employeeId = $employeeId;
            $newAttendance->calendarId = 0;
            $newAttendance->shiftId = $summarySaved->shiftId;
            $newAttendance->timeZone = $timeZone;
            $newAttendance->earlyOut = 0;
            $newAttendance->lateIn =  $lateCountInMin;
            $newAttendance->workedHours = 0;
            $newAttendance->summaryId = $summarySaved->id;

            // set summary first in time
            $summaryData->firstIn = $attendancePunchedDate->format("Y-m-d H:i:s e");
            $summaryData->firstInUTC = $attendancePunchedDateTimeUTC->format("Y-m-d H:i:s e");

            if (is_null($summarySaved->actualIn)) {
                $updateSummary = new stdClass();
                $updateSummary = $summarySaved;
                $updateSummary->actualIn = $summaryData->firstIn;
                $updateSummary->actualInUTC = $summaryData->firstInUTC;
                $updateSummaryArray = (array) $updateSummary;

                $summaryUpdated = $this->store->updateById($this->attendance_summary_model, $summarySaved->id, $updateSummaryArray, true);

                if (!$summaryUpdated) {
                    return [
                        'syncState' => false
                    ]; 
                }
            }

            $newAttendanceArray = (array) $newAttendance;
            $savedNewAttendance = $this->store->insert($this->attendanceModel, $newAttendanceArray, true);

            if (!$savedNewAttendance) {
                return [
                    'syncState' => false
                ]; 
            }
        } else {
            // punched out add
            $expectedTime = Carbon::parse($summarySaved->expectedOut, $timeZone);
            $attendancePunchedDate = Carbon::parse($attendanceRecord['recordTime'], $timeZone);
            $attendancePunchedDateTimeUTC = $attendancePunchedDate->copy()->tz('UTC');
            $earlyCountInSec = $this->timeDiffSeconds($attendancePunchedDate, $expectedTime, true);
            $earlyCountInMin = (int) ($earlyCountInSec / 60);
            $formattedEarlyCount = gmdate("H:i", $earlyCountInSec);

            // $inDateTimeUtc =  new DateTime($attendanceSaved->inUTC, new DateTimeZone('UTC'));
            $inDateTimeUtc =  Carbon::parse($attendanceSaved->inUTC, 'UTC');
            // $inWithZone = $inDateTimeUtc->setTimezone(new DateTimeZone($timeZone));
            $inWithZone = $inDateTimeUtc->copy()->tz($timeZone);
            $attendanceWorkedCountInSec = $this->timeDiffSeconds($attendancePunchedDate, $inWithZone, false);
            $workedCountInSec = $attendanceWorkedCountInSec - ($attendanceSaved->breakHours ? $attendanceSaved->breakHours * 60 : 0);
            $workedCountInMin = (int) ($workedCountInSec / 60);

            $updateAttendance = new stdClass();
            $updateAttendance->date =  $attendanceSaved->date;
            $updateAttendance->in =  $attendanceSaved->in;
            $updateAttendance->inUTC =  $attendanceSaved->inUTC;
            $updateAttendance->out = $attendancePunchedDate->format("Y-m-d H:i:s e");
            $updateAttendance->outUTC = $attendancePunchedDateTimeUTC->format("Y-m-d H:i:s e");
            $updateAttendance->typeId =  $attendanceSaved->typeId;
            $updateAttendance->employeeId = $attendanceSaved->employeeId;
            $updateAttendance->summaryId = $attendanceSaved->summaryId;
            $updateAttendance->calendarId =  $attendanceSaved->calendarId;
            $updateAttendance->shiftId =  $attendanceSaved->shiftId;
            $updateAttendance->earlyOut = $earlyCountInMin;
            $updateAttendance->lateIn = $attendanceSaved->lateIn;
            $updateAttendance->workedHours = $workedCountInMin;
            $updateAttendance->timeZone = $attendanceSaved->timeZone;

            // set summary last out time
            $summaryData->lastOut = $attendancePunchedDate->format("Y-m-d H:i:s e");
            $summaryData->lastOutUTC = $attendancePunchedDateTimeUTC->format("Y-m-d H:i:s e");

            if (!is_null($summarySaved)) {
                // update summary actual out
                $summaryId = $summarySaved->id;
                $updateSummary = new stdClass();
                $updateSummary = $summarySaved;
                $updateSummary->actualOut = $summaryData->lastOut;
                $updateSummary->actualOutUTC = $summaryData->lastOutUTC;
                $updateSummaryArray = (array) $updateSummary;

                $summarySaved = $this->store->updateById($this->attendance_summary_model, $summarySaved->id, $updateSummaryArray, true);
            }
        
            $updatedAttendance = $this->store->updateById($this->attendanceModel, $attendanceSaved->id, (array) $updateAttendance, true);

            if (!$updatedAttendance) {
                return [
                    'syncState' => false
                ]; 
            }
        }

        return [
            'syncState' => true,
            'shiftDate' => $shiftDate
        ]; 
    }

    /**
     * Get attendance summary record for sync
     * 
     */
    private function getAttendanceSummaryForSync($employeeId, $currentTime, $timeZone)
    {
        try {

            $date = $currentTime->format('Y-m-d');
            $summarySaved = $this->store->getFacade()::table('attendance_summary')->where('employeeId', $employeeId)->where('date', $date)->orderBy("expectedIn", "asc")->get();
            $associatedSummary = null;

            if (count($summarySaved) === 0) {
                $this->callAttendanceSummaryAdd($employeeId, $date);

                $summarySaved = $this->store->getFacade()::table('attendance_summary')->where('employeeId', $employeeId)->where('date', $date)->orderBy("expectedIn", "asc")->get();
            }

            return $summarySaved->first();
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    /**
     * Call attendance summary add
     * 
     */
    private function callAttendanceSummaryAdd($employeeId, $date)
    {
        $this->processEmployeeAttendance($employeeId, [$date]);
    }
}
