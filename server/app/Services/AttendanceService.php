<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;
use App\Library\Session;
use App\Library\Store;
use App\Library\JsonModel;
use App\Library\Interfaces\ModelReaderInterface;
use App\Traits\JsonModelReader;
use App\Traits\AttendanceSync;
use App\Traits\AttendanceProcess;
use App\Exports\AttendanceExcelExport;
use App\Exports\AttendanceReportExcelExport;
use App\Traits\EmployeeHelper;
use App\Traits\ConfigHelper;
use Carbon\Carbon;
use App\Library\ModelValidator;
use DateTime;
use DateTimeZone;
use \Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Events\AttendanceDateDetailChangedEvent;
use stdClass;

class AttendanceService extends BaseService
{
    use JsonModelReader;
    use AttendanceProcess;
    use EmployeeHelper;
    use ConfigHelper;
    use AttendanceSync;

    private $store;
    private $attendanceModel;
    private $breakModel;
    protected $session;
    private $workflowService;

    private $clockIn = 'CLOCK IN';
    private $clockOut = 'CLOCK OUT';
    private $breakIn = 'BREAK IN';
    private $breakOut = 'BREAK OUT';
    private $reset = 'RESET';

    public function __construct(Store $store, Session $session, WorkflowService $ws)
    {
        $this->store = $store;
        $this->session = $session;
        $this->attendance_summary_model = $this->getModel('attendanceSummary', true);
        $this->attendanceModel = $this->getModel('attendance', true);
        $this->breakModel = $this->getModel('break', true);
        $this->attendance_history_model = $this->getModel('attendanceHistory', true);
        $this->break_history_model = $this->getModel('breakHistory', true);
        $this->attendance_timeChange_model = $this->getModel('attendanceTimeChange', true);
        $this->workflowInstanceModel = $this->getModel('workflowInstance', true);
        $this->postOtRequestModel = $this->getModel('postOtRequest', true);
        $this->postOtRequestDetailModel = $this->getModel('postOtRequestDetail', true);
        $this->workflowService = $ws;
    }

    public function manageAttendance($currentStatus)
    {
        try {
            // check state different and refresh the data
            $attendanceDataResponce = (object) $this->getAttendanceRelatedData();
            $currentAttendanceData = $attendanceDataResponce->data;
            if ($currentStatus !== $currentAttendanceData->status) {
                $currentAttendanceData->resetStatus = $this->reset;
                return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $currentAttendanceData);
            }

            $employeeId = $this->session->getEmployee()->id;

            $dateObj = Carbon::now('UTC');
            $company = DB::table('company')->first('timeZone');
            $companyDate = $dateObj->copy()->tz($company->timeZone);
            $employeeJob = $this->getEmployeeJob($employeeId, $companyDate->format('Y-m-d'), ['calendarId', 'locationId']);

            if (!$employeeId || !$employeeJob) {
                return $this->error(404, Lang::get('attendanceMessages.basic.ERR_NOT_EXIST'));
            }

            $location = $this->store->getFacade()::table('location')->where('id', $employeeJob->locationId)->first();
            if (!$location) {
                return $this->error(404, Lang::get('attendanceMessages.basic.ERR_NOT_EXIST'));
            }

            $timeZone =  $location->timeZone;

            $givenTimeUTC = $dateObj->copy();
            $punched_date = $givenTimeUTC->copy()->tz($timeZone);

            $summarySaved = $this->getAttendanceSummary($employeeId, $punched_date, $timeZone);
            $shiftDate = $summarySaved->date;

            $shiftName = null;
            $summaryData = new stdClass();
            $summaryData->date = $shiftDate;
            $summaryData->timeZone = $timeZone;
            $summaryData->employeeId = $employeeId;
            $shiftData = null;

            if (!is_null($summarySaved->shiftId)) {
                $shiftData = $this->store->getFacade()::table('workShifts')
                    ->leftJoin('workShiftDayType', 'workShiftDayType.workShiftId', '=', 'workShifts.id')
                    ->where('workShiftDayType.dayTypeId', $summarySaved->dayTypeId)
                    ->where('workShifts.id', $summarySaved->shiftId)->first();

                if (empty($shiftData->name)) {
                    $workPattern = $this->getWorkPatternByShiftId($summarySaved->shiftId);
                    $shiftName = isset($workPattern->name) ? $workPattern->name : null;
                }
            }

            $attendanceSaved = $this->store->getFacade()::table('attendance')->where('summaryId', $summarySaved->id)->where('employeeId', $employeeId)->where('date', $shiftDate)->orderBy("in", "desc")->first();

            $isNewAttendance = is_null($attendanceSaved);
            $isPunchedInOnly = !is_null($attendanceSaved) && (is_null($attendanceSaved->out) || $attendanceSaved->out == '0000-00-00 00:00:00');

            if ($isNewAttendance || !$isPunchedInOnly) {
                // clock in add
                if (!is_null($shiftData) && $shiftData->shiftType == 'FLEXI') {
                    if ($shiftData->endTime == '00:00' && $shiftData->startTime == '00:00') {
                        $expectedTime = NULL;
                    } else {
                        $expectedInForFlexi = $summarySaved->date.' '.$shiftData->endTime;
                        $expectedTime = (!is_null($shiftData) && $shiftData->isBehaveAsNonWorkingDay) ? NULL : new DateTime($expectedInForFlexi, new DateTimeZone($timeZone));
                    }

                } else {
                    $expectedTime = (!is_null($shiftData) && $shiftData->isBehaveAsNonWorkingDay) ? NULL : new DateTime($summarySaved->expectedIn, new DateTimeZone($timeZone));
                }

                $gracePeriod = (!is_null($shiftData) && !is_null($shiftData->gracePeriod)) ? (int) $shiftData->gracePeriod : 0;

                $lateCountInSec = (is_null($expectedTime)) ? 0 : $this->timeDiffSeconds($punched_date, $expectedTime, false);
                $lateCountInMin = ($lateCountInSec > 0) ? (int) ($lateCountInSec / 60) : 0;

                $newAttendance = new stdClass();
                $newAttendance->date = $shiftDate;
                $newAttendance->in = $punched_date->format("Y-m-d H:i:s e");
                $newAttendance->inUTC = $givenTimeUTC->format("Y-m-d H:i:s e");
                $newAttendance->typeId = 0;
                $newAttendance->employeeId = $employeeId;
                $newAttendance->calendarId = 0;
                $newAttendance->shiftId = $summarySaved->shiftId;
                $newAttendance->timeZone = $timeZone;
                $newAttendance->earlyOut = 0;
                $newAttendance->lateIn =  ($lateCountInMin > 0 && $lateCountInMin > $gracePeriod) ? $lateCountInMin : 0;
                $newAttendance->workedHours = 0;
                $newAttendance->summaryId = $summarySaved->id;

                // set summary first in time
                $summaryData->firstIn = $punched_date->format("Y-m-d H:i:s e");
                $summaryData->firstInUTC = $givenTimeUTC->format("Y-m-d H:i:s e");
                $summaryData->lateIn = ($lateCountInMin > 0 && $lateCountInMin > $gracePeriod) ? $lateCountInMin : 0;;

                if (is_null($summarySaved->actualIn) || $summarySaved->actualIn == '0000-00-00 00:00:00') {
                    $updateSummary = new stdClass();
                    $updateSummary = $summarySaved;
                    $updateSummary->actualIn = $summaryData->firstIn;
                    $updateSummary->actualInUTC = $summaryData->firstInUTC;
                    $updateSummary->isLateIn = $lateCountInMin > 0 && $lateCountInMin > $gracePeriod;
                    $updateSummary->lateIn = ($lateCountInMin > 0 && $lateCountInMin > $gracePeriod) ? $lateCountInMin : 0;;
                    $updateSummary->isPresent = true;
                    $updateSummary->isNoPay = false;
                    $updateSummaryArray = (array) $updateSummary;

                    $summaryUpdated = $this->store->updateById($this->attendance_summary_model, $summarySaved->id, $updateSummaryArray, true);

                    if (!$summaryUpdated) {
                        return $this->error(502, Lang::get('attendanceMessages.basic.ERR_UPDATE'), $summaryUpdated);
                    }
                }

                $newAttendanceArray = (array) $newAttendance;
                $savedNewAttendance = $this->store->insert($this->attendanceModel, $newAttendanceArray, true);

                if (!$savedNewAttendance) {
                    return $this->error(502, Lang::get('attendanceMessages.basic.ERR_CREATE'), $savedNewAttendance);
                }

                $returnAttendance = new stdClass();
                $returnAttendance->status = $this->clockOut;
                $returnAttendance->zone = $timeZone;

                //get updated summary record
                $updatedSummaryRecord = $this->store->getFacade()::table('attendance_summary')->where('employeeId', $employeeId)->where('date', $shiftDate)->first();

                $returnAttendance = $this->getAttendanceSummaryDataDrawerView($updatedSummaryRecord, $returnAttendance);

                return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_CREATE'), $returnAttendance);
            } else {
                // clock out add
                $inDateTimeUtc = new DateTime($attendanceSaved->inUTC, new DateTimeZone('UTC'));
                $inWithZone = $inDateTimeUtc->setTimezone(new DateTimeZone($timeZone));
                $attendanceWorkedCountInSec = $this->timeDiffSeconds($punched_date, $inWithZone, false);
                $workedCountInSec = $attendanceWorkedCountInSec - ($attendanceSaved->breakHours ? $attendanceSaved->breakHours * 60 : 0);
                $workedCountInMin = (int) ($workedCountInSec / 60);
                $dates = [];
                $dates[] = $shiftDate;


                if (!is_null($shiftData) && $shiftData->shiftType == 'GENENRAL') {
                    $expectedTime = (!is_null($shiftData) && $shiftData->isBehaveAsNonWorkingDay) ? NULL : new DateTime($summarySaved->expectedOut, new DateTimeZone($timeZone));
                    $earlyCountInSec = (is_null($expectedTime)) ? 0 : $this->timeDiffSeconds($punched_date, $expectedTime, true);
                } else {
                    $earlyCountInSec = null;
                    if ((!is_null($shiftData) && !$shiftData->isBehaveAsNonWorkingDay)) {
                        $expectedWorkTime = is_null($shiftData->workHours) ? 0 : (int) $shiftData->workHours;
                        $workTime = (int) $summarySaved->workTime + $workedCountInMin;
        
                        if ($expectedWorkTime > $workTime) {
                            $earlyOut = $expectedWorkTime - $workTime;
                            $earlyCountInSec = $earlyOut * 60;
                        } 
                    }
                }
                $earlyCountInMin = ($earlyCountInSec > 0) ? (int) ($earlyCountInSec / 60) : 0;
                $formattedEarlyCount = gmdate("H:i", $earlyCountInSec);


                $updateAttendance = new stdClass();
                $updateAttendance->date =  $attendanceSaved->date;
                $updateAttendance->in =  $attendanceSaved->in;
                $updateAttendance->inUTC =  $attendanceSaved->inUTC;
                $updateAttendance->out = $punched_date->format("Y-m-d H:i:s e");
                $updateAttendance->outUTC = $givenTimeUTC->format("Y-m-d H:i:s e");
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
                $summaryData->lastOut = $punched_date->format("Y-m-d H:i:s e");
                $summaryData->lastOutUTC = $givenTimeUTC->format("Y-m-d H:i:s e");
                $summaryData->isEarlyOut = $earlyCountInMin > 0;
                $summaryData->earlyOut = $earlyCountInMin;
                $summaryData->workTime = $workedCountInMin;


                $updatedAttendance = $this->store->updateById($this->attendanceModel, $attendanceSaved->id, (array) $updateAttendance, true);

                if (!$updatedAttendance) {
                    return $this->error(502, Lang::get('attendanceMessages.basic.ERR_CREATE'), $updatedAttendance);
                }

                if (!is_null($summarySaved)) {

                    if (!is_null($shiftData) && $shiftData->isBehaveAsNonWorkingDay) {
                        $shiftWiseWorkTime = [
                            'preShiftWorkTime' => 0,
                            'withinShiftWorkTime' => 0,
                            'postShiftWorkTime' => $summarySaved->workTime + $summaryData->workTime
                        ];
                    } else {

                        $expectedIn = $summarySaved->expectedIn;
                        $expectedOut = $summarySaved->expectedOut;
                        if (!is_null($shiftData) && $shiftData->shiftType == 'FLEXI' && !is_null($summarySaved->actualIn)) {

                            $expectedIn = $summarySaved->actualIn;
                            $actualInObj = Carbon::parse($summarySaved->actualIn);
                            $expectedWorkTime = !is_null($summarySaved->expectedWorkTime) ? (int) $summarySaved->expectedWorkTime : 0;
                            $expectOutObj = $actualInObj->copy()->addMinutes($expectedWorkTime);
                            $expectedOut = $expectOutObj->format('Y-m-d H:i');
                        }
                        $shiftWiseWorkTime = $this->calculateWorkTimeAccordingToShiftPeriod($summarySaved->id, $employeeId, $shiftDate, $expectedIn, $expectedOut);
                    }

                    $summaryId = $summarySaved->id;
                    // update summary
                    $updateSummary = new stdClass();
                    $updateSummary = $summarySaved;
                    $updateSummary->actualOut = $summaryData->lastOut;
                    $updateSummary->actualOutUTC = $summaryData->lastOutUTC;
                    $updateSummary->isEarlyOut = $summaryData->isEarlyOut;
                    $updateSummary->earlyOut = $summaryData->earlyOut;
                    $updateSummary->workTime = $summarySaved->workTime + $summaryData->workTime;
                    $updateSummary->preShiftWorkTime = $shiftWiseWorkTime['preShiftWorkTime'];
                    $updateSummary->withinShiftWorkTime = $shiftWiseWorkTime['withinShiftWorkTime'];
                    $updateSummary->postShiftWorkTime = $shiftWiseWorkTime['postShiftWorkTime'];
                    $updateSummaryArray = (array) $updateSummary;

                    error_log(json_encode($updateSummaryArray));

                    $summarySaved = $this->store->updateById($this->attendance_summary_model, $summarySaved->id, $updateSummaryArray, true);
                    $summaryPayTypeDetailsSaved =  $this->calculateAttendanceRelatedOTDetails($summaryId, $updateSummaryArray);
                }

                $dataSet = [
                    'employeeId' => $employeeId,
                    'dates' => $dates
                ];
    
                event(new AttendanceDateDetailChangedEvent($dataSet));

                $returnAttendance = new stdClass();
                $returnAttendance->zone = $timeZone;
                $returnAttendance->status = $this->clockIn;

                //get updated summary record
                $updatedSummaryRecord = $this->store->getFacade()::table('attendance_summary')->where('employeeId', $employeeId)->where('date', $shiftDate)->first();
                $returnAttendance = $this->getAttendanceSummaryDataDrawerView($updatedSummaryRecord, $returnAttendance);

                return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_CREATE'), $returnAttendance);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function getAttendanceRelatedData($isShiftSummary = false)
    {
        try {
            $employeeId = $this->session->getEmployee()->id;

            $dateObj = Carbon::now('UTC');
            $company = DB::table('company')->first('timeZone');
            $companyDate = $dateObj->copy()->tz($company->timeZone);
            $dayName = $companyDate->format('l');

            $employeeJob = $this->getEmployeeJob($employeeId, $companyDate->format('Y-m-d'), ['calendarId', 'locationId']);

            if (!$employeeId || !$employeeJob) {
                return $this->error(404, Lang::get('attendanceMessages.basic.ERR_NOT_EXIST'));
            }

            $location = $this->store->getFacade()::table('location')->where('id', $employeeJob->locationId)->first();

            if (!$location) {
                return $this->error(404, Lang::get('attendanceMessages.basic.ERR_NOT_EXIST'));
            }

            $returnAttendance = new stdClass();
            $returnAttendance->zone =  $location->timeZone;

            $checkDate = Carbon::now($returnAttendance->zone);
            // TODO:: should enhance getAttendanceSummary function for detect current shift
            $summarySaved = $this->getAttendanceSummary($employeeId, $checkDate, $location->timeZone);
            $checkDateString = $summarySaved->date;
            $shiftName = null;
            $shiftRange = null;

            // if shift exist
            if (!is_null($summarySaved->shiftId)) {
                // get day type id
                $dayTypeId = $this->getCalendarDayTypeId($employeeJob->calendarId, $dayName);
                // get shift related data
                $shiftData = $this->store->getFacade()::table('workShifts')
                    ->join('workShiftDayType', 'workShiftDayType.workShiftId', '=', 'workShifts.id')
                    ->where('workShifts.id', $summarySaved->shiftId)
                    ->where('workShiftDayType.dayTypeId', $dayTypeId)
                    ->first(['workShifts.name', 'workShiftDayType.startTime', 'workShiftDayType.endTime', 'workShiftDayType.isBehaveAsNonWorkingDay', 'workShifts.shiftType']);
                $shiftName = $shiftData->name;
                $shiftRange = "$shiftData->startTime - $shiftData->endTime";
            }
            $returnAttendance->shift = $shiftName;
            $returnAttendance->shiftRange = $shiftRange;

            // all attendance records for date
            $attendances = $this->store->getFacade()::table('attendance')->where('summaryId', $summarySaved->id)->where('employeeId', $employeeId)->where('date', $checkDateString)->orderBy("in", "desc")->get();

            // get the last attendance in record
            $lastInattendance = $attendances->first();


            $isNewAttendance = is_null($lastInattendance);
            $isPunchedInOnly = !is_null($lastInattendance) && is_null($lastInattendance->out);

            if ($isShiftSummary && $attendances->count() > 0) {

                $actualIn = new DateTime($summarySaved->actualIn, new DateTimeZone($location->timeZone));
                $actualOut = new DateTime($summarySaved->actualOut, new DateTimeZone($location->timeZone));

                if (!is_null($shiftData) && $shiftData->shiftType == 'FLEXI') {
                    if ($shiftData->endTime == '00:00' && $shiftData->startTime == '00:00') {
                        $expectedIn = NULL;
                    } else {
                        $expectedInForFlexi = $summarySaved->date.' '.$shiftData->endTime;
                        $expectedIn = (!is_null($shiftData) && $shiftData->isBehaveAsNonWorkingDay) ? NULL : new DateTime($expectedInForFlexi, new DateTimeZone($timeZone));
                    }
                } else {
                    $expectedIn = (!is_null($shiftData) && $shiftData->isBehaveAsNonWorkingDay) ? NULL : new DateTime($summarySaved->expectedIn, new DateTimeZone($location->timeZone));
                }

                $lateCountInSec = (is_null($expectedIn)) ? 0 : $this->timeDiffSeconds($actualIn, $expectedIn, false);



                if (!is_null($shiftData) && $shiftData->shiftType == 'GENERAL') {
                    $expectedOut = (!is_null($shiftData) && $shiftData->isBehaveAsNonWorkingDay) ? NULL : new DateTime($summarySaved->expectedOut, new DateTimeZone($location->timeZone));
                    $earlyCountOutSec = !is_null($expectedOut)  ? $this->timeDiffSeconds($actualOut, $expectedOut, true) : 0;
                } else {
                    if (!is_null($actualOut) && !is_null($shiftData)) {
                        $earlyCountOutSec = 0;

                        if (!$shiftData->isBehaveAsNonWorkingDay) {
                            $expectedWorkTime = is_null($shiftData->workHours) ? 0 : (int) $shiftData->workHours;
                            $workTime = (int) $summarySaved->workTime;
            
                            if ($expectedWorkTime > $workTime) {
                                $earlyOut = $expectedWorkTime - $workTime;
                                $earlyCountOutSec = $earlyOut * 60;
                            } 
                        }
        
                    } else {
                        $earlyCountOutSec  = 0;
                    }

                }
                
                $returnAttendance->clockIn =  $actualIn->format('Y-m-d H:i:sP');
                $returnAttendance->clockOut = $actualOut->format('Y-m-d H:i:sP');
                $returnAttendance->early = gmdate("H:i:s", $earlyCountOutSec);
                $returnAttendance->late = gmdate("H:i:s", $lateCountInSec);
                $returnAttendance->workedHours = gmdate("H:i:s", $summarySaved->workTime * 60);
                $returnAttendance->totalBreak = gmdate("H:i:s", $summarySaved->breakTime * 60);
                $returnAttendance->date =  $summarySaved->date;
                $returnAttendance->status = $this->clockIn;
                return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $returnAttendance);
            }

            if ($isNewAttendance || !$isPunchedInOnly) {
                // new, not In
                $returnAttendance->status = $this->clockIn;
                $returnAttendance = $this->getAttendanceSummaryDataDrawerView($summarySaved, $returnAttendance, $lastInattendance);

                return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $returnAttendance);
            } else {
                // In
                $returnAttendance->extraHours = $lastInattendance->earlyOut * 60;
                $returnAttendance->requiredHours = $lastInattendance->lateIn * 60;

                $returnAttendance = $this->getAttendanceSummaryDataDrawerView($summarySaved, $returnAttendance, $lastInattendance);
                $breakSaved = $this->store->getFacade()::table('break')->where('attendanceId', $lastInattendance->id)->orderBy("in", "desc")->first();

                $isNewBreak = is_null($breakSaved);
                $isPunchedInBreakOnly = !is_null($breakSaved) && (is_null($breakSaved->out) || $breakSaved->out == '0000-00-00 00:00:00');

                if (!$isNewBreak && $isPunchedInBreakOnly) {
                    // clocked in and in break

                    $inBreakWithZone = new DateTime($breakSaved->in, new DateTimeZone($location->timeZone));

                    $returnAttendance->status = $this->breakOut;
                    $returnAttendance->breakInTime = $inBreakWithZone->format('Y-m-d H:i:sP');

                    return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $returnAttendance);
                } else {
                    // clocked in and working
                    $returnAttendance->status = $this->clockOut;

                    return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $returnAttendance);
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }


    private function getAttendanceSummaryDataDrawerView($summeryRecord, $returnAttendance, $lastInattendance = false)
    {
        if (is_null($summeryRecord)) {
            return $returnAttendance;
        }

        if (!$lastInattendance) {
            // all attendance records for date
            $attendances = $this->store->getFacade()::table('attendance')->where('summaryId', $summeryRecord->id)->where('employeeId', $summeryRecord->employeeId)->where('date', $summeryRecord->date)->orderBy("in", "desc")->get();

            // get the last attendance in record
            $lastInattendance = $attendances->first();
        }

        $shiftData = null;
        // if shift exist
        if (!is_null($summeryRecord->shiftId)) {
            // get shift related data
            $shiftData = $this->store->getFacade()::table('workShifts')
                ->join('workShiftDayType', 'workShiftDayType.workShiftId', '=', 'workShifts.id')
                ->where('workShifts.id', $summeryRecord->shiftId)
                ->where('workShiftDayType.dayTypeId', $summeryRecord->dayTypeId)
                ->first(['workShifts.name', 'workShiftDayType.startTime', 'workShiftDayType.endTime', 'workShiftDayType.isBehaveAsNonWorkingDay', 'workShifts.shiftType', 'workShiftDayType.workHours']);
        }

        // $expectedOut =  new DateTime($summeryRecord->expectedOut, new DateTimeZone($returnAttendance->zone));
        $actualIn = (!empty($summeryRecord) && $summeryRecord->actualIn) ? new DateTime($summeryRecord->actualIn, new DateTimeZone($returnAttendance->zone)): null;
        $currentIn = (!empty($lastInattendance) && $lastInattendance->in) ? new DateTime($lastInattendance->in, new DateTimeZone($returnAttendance->zone)): null;
        $actualOut = (!empty($lastInattendance) && $lastInattendance->out) ? new DateTime($lastInattendance->out, new DateTimeZone($returnAttendance->zone)) : null;
        
        if (!is_null($shiftData) && $shiftData->shiftType == 'FLEXI') {
            if ($shiftData->endTime == '00:00' && $shiftData->startTime == '00:00') {
                $expectedIn = NULL;
            } else {
                $expectedInForFlexi = $summeryRecord->date.' '.$shiftData->endTime;
                $expectedIn = (!is_null($shiftData) && $shiftData->isBehaveAsNonWorkingDay) ? NULL : new DateTime($expectedInForFlexi, new DateTimeZone($returnAttendance->zone));
            }
        } else {
            $expectedIn = (!is_null($shiftData) && $shiftData->isBehaveAsNonWorkingDay) ? NULL : new DateTime($summeryRecord->expectedIn, new DateTimeZone($returnAttendance->zone));
        }

        $lateCountInSec = (!is_null($actualIn) && !is_null($expectedIn)) ? $this->timeDiffSeconds($actualIn, $expectedIn, false) : 0;

        //calculate early out
        if (!is_null($shiftData) && $shiftData->shiftType == 'GENERAL') {
            $expectedOut = (!is_null($shiftData) && $shiftData->isBehaveAsNonWorkingDay) ? NULL :  new DateTime($summeryRecord->expectedOut, new DateTimeZone($returnAttendance->zone));
            $earlyCountOutSec = (!is_null($actualOut)) && !is_null($expectedOut) ? $this->timeDiffSeconds($actualOut, $expectedOut, true) : 0;
        } else {
            if (!is_null($actualOut) && !is_null($shiftData)) {
                $earlyCountOutSec = 0;

                if (!$shiftData->isBehaveAsNonWorkingDay) {
                    $expectedWorkTime =  is_null($shiftData->workHours) ? 0 : (int) $shiftData->workHours;
                    $workTime = (int) $summeryRecord->workTime;
    
                    if ($expectedWorkTime > $workTime) {
                        $earlyOut = $expectedWorkTime - $workTime;
                        $earlyCountOutSec = $earlyOut * 60;
                    } 
                }

            } else {
                $earlyCountOutSec  = 0;
            }
        }

        
        
        

        $returnAttendance->clockIn = (!is_null($actualIn)) ? $actualIn->format('Y-m-d H:i:sP') : null;
        $returnAttendance->currentClockIn = (!is_null($currentIn)) ? $currentIn->format('Y-m-d H:i:sP') : null;
        $returnAttendance->clockOut = (!is_null($actualOut)) ? $actualOut->format('Y-m-d H:i:sP') : null;
        $returnAttendance->early = gmdate("H:i:s", $earlyCountOutSec);
        $returnAttendance->late = gmdate("H:i:s", $lateCountInSec);
        $returnAttendance->workedHours = gmdate("H:i:s", $summeryRecord->workTime * 60);
        $returnAttendance->breakHours = (!empty($lastInattendance)) ?  $lastInattendance->breakHours * 60 : 0;
        $returnAttendance->totalBreak = gmdate("H:i:s", $summeryRecord->breakTime * 60);
        $returnAttendance->currentTotalBreak =  gmdate("H:i:s",(!empty($lastInattendance) && !empty($lastInattendance->breakHours)) ? $lastInattendance->breakHours * 60 : 0);
        $returnAttendance->date =  $summeryRecord->date;

        if (!empty($lastInattendance)) {
            //get recent break 
            $recentBreakRecord =  $this->store->getFacade()::table('break')
                ->select('break.*')
                ->leftJoin('attendance', 'attendance.id', '=', 'break.attendanceId')
                ->where('attendance.summaryId',$summeryRecord->id)->whereNotNull('break.in')->whereNotNull('break.out')->orderBy("break.in", "desc")->first();
            $recentBreakTime = '00:00:00';
            if (!is_null($recentBreakRecord)) {
                $recentBreakTime = gmdate("H:i:s", $recentBreakRecord->diff * 60);
            }
            $returnAttendance->recentBreakTime = $recentBreakTime;
        } else {
            $returnAttendance->recentBreakTime = '00:00:00';
        }

        return $returnAttendance;
    }

    public function manageBreak($currentStatus)
    {
        try {
            // check state different and refresh the data
            $attendanceDataResponce = (object) $this->getAttendanceRelatedData();
            $currentAttendanceData = $attendanceDataResponce->data;
            if ($currentStatus !== $currentAttendanceData->status) {
                $currentAttendanceData->resetStatus = $this->reset;
                return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $currentAttendanceData);
            }

            $employeeId = $this->session->getEmployee()->id;

            $dateObj = Carbon::now('UTC');
            $company = DB::table('company')->first('timeZone');
            $companyDate = $dateObj->copy()->tz($company->timeZone);
            $employeeJob = $this->getEmployeeJob($employeeId, $companyDate->format('Y-m-d'), ['calendarId', 'locationId']);;

            if (!$employeeId || !$employeeJob) {
                return $this->error(404, Lang::get('attendanceMessages.basic.ERR_NOT_EXIST'));
            }

            $location = $this->store->getFacade()::table('location')->where('id', $employeeJob->locationId)->first();

            if (!$location) {
                return $this->error(404, Lang::get('attendanceMessages.basic.ERR_NOT_EXIST'));
            }

            $zone =  $location->timeZone;
            $punchedDateTime = $dateObj->copy()->tz($zone);
            $punchedDateTimeString = $punchedDateTime->format('Y-m-d H:i:s e');

            $summarySaved = $this->getAttendanceSummary($employeeId, $punchedDateTime, $location->timeZone);
            $shiftDate = $summarySaved->date;

            $attendanceSaved = $this->store->getFacade()::table('attendance')->where('summaryId', $summarySaved->id)->where('employeeId', $employeeId)->where('date', $shiftDate)->orderBy("in", "desc")->first();

            $isNewAttendance = is_null($attendanceSaved);
            $isPunchedInOnly = !is_null($attendanceSaved) && (is_null($attendanceSaved->out) || $attendanceSaved->out == '0000-00-00 00:00:00');

            if (!$isNewAttendance && $isPunchedInOnly) {
                // check in
                $breakSaved = $this->store->getFacade()::table('break')->where('attendanceId', $attendanceSaved->id)->orderBy("in", "desc")->first();

                $isNewBreak = is_null($breakSaved);
                $isPunchedInBreakOnly = !is_null($breakSaved) && (is_null($breakSaved->out) || $breakSaved->out == '0000-00-00 00:00:00');

                if (!$isNewBreak && $isPunchedInBreakOnly) {
                    // if (!is_null($breakSaved) && (is_null($breakSaved->out) ||  $breakSaved->out == '0000-00-00 00:00:00')) {
                    // check in and break in -> need to update

                    $breakStart = new DateTime($breakSaved->in, new DateTimeZone($zone));
                    $breakCountInSec = $this->timeDiffSeconds($punchedDateTime, $breakStart, false);
                    $breakCountInMin = (int) ($breakCountInSec / 60);

                    $updateBreak = new stdClass();
                    $updateBreak = $breakSaved;
                    $updateBreak->out =  $punchedDateTimeString;
                    $updateBreak->diff =  $breakCountInMin;
                    $updatedBreak = $this->store->updateById($this->breakModel, $breakSaved->id, (array) $updateBreak, true);

                    if (!$updatedBreak) {
                        return $this->error(502, Lang::get('attendanceMessages.basic.ERR_UPDATE'), $updatedBreak);
                    }

                    $updateAttendance = new stdClass();
                    $updateAttendance = $attendanceSaved;
                    $updateAttendance->breakHours = $breakCountInMin + $attendanceSaved->breakHours;
                    $updatedAttendance = $this->store->updateById($this->attendanceModel, $attendanceSaved->id, (array) $updateAttendance, true);
                    if (!$updatedAttendance) {
                        return $this->error(502, Lang::get('attendanceMessages.basic.ERR_UPDATE'), $updatedAttendance);
                    }

                    // update summary attendance total break time
                    $updateSummary = new stdClass();
                    $updateSummary = $summarySaved;
                    $updateSummary->breakTime = $summarySaved->breakTime + $breakCountInMin;
                    $updateSummaryArray = (array) $updateSummary;

                    $updatedSummary = $this->store->updateById($this->attendance_summary_model, $summarySaved->id, $updateSummaryArray, true);

                    $returnAttendance = new stdClass();
                    $returnAttendance->status = $this->clockOut;
                    $returnAttendance->zone = $zone;

                    //get updated summary record
                    $updatedSummaryRecord = $this->store->getFacade()::table('attendance_summary')->where('employeeId', $employeeId)->where('date', $shiftDate)->first();
                    $returnAttendance = $this->getAttendanceSummaryDataDrawerView($updatedSummaryRecord, $returnAttendance);

                    return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_UPDATE'), $returnAttendance);
                } else {
                    // add
                    $addBreak = new stdClass();
                    $addBreak->in = $punchedDateTimeString;
                    $addBreak->attendanceId =  $attendanceSaved->id;
                    $save = (array) $addBreak;

                    $savedNewBreak = $this->store->insert($this->breakModel, $save, true);
                    if (!$savedNewBreak) {
                        return $this->error(502, Lang::get('attendanceMessages.basic.ERR_CREATE'), $savedNewBreak);
                    }

                    $returnAttendance = new stdClass();
                    $returnAttendance->status = $this->breakOut;
                    $returnAttendance->zone = $zone;

                    //get updated summary record
                    $updatedSummaryRecord = $this->store->getFacade()::table('attendance_summary')->where('employeeId', $employeeId)->where('date', $shiftDate)->first();
                    $returnAttendance = $this->getAttendanceSummaryDataDrawerView($updatedSummaryRecord, $returnAttendance);

                    $returnAttendance->breakInTime = $punchedDateTime->format('Y-m-d H:i:sP');

                    return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_CREATE'), $returnAttendance);
                }
            } else {
                return $this->error(404, Lang::get('attendanceMessages.basic.ERR_NOT_EXIST'));
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function getManagerAttendanceSheetData($request)
    {
        try {
            $employeeId = $request->query('employee', null);
            $fromDate = $request->query('fromDate', null);
            $toDate = $request->query('toDate', null);
            $pageNo = $request->query('pageNo', null);
            $pageCount = $request->query('pageCount', null);
            $sort = json_decode($request->query('sort', null));
            $employeeId = json_decode($employeeId);

            $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();


            // if (!is_null($employeeId) && !in_array($employeeId, $permittedEmployeeIds)) {
            //     return $this->error(403, Lang::get('attendanceMessages.basic.ERR_NOT_PERMITTED'), null);
            // }

            $attendanceSheets = $this->getAttendanceSheetData($employeeId, $fromDate, $toDate, $pageNo, $pageCount, $sort);

            return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $attendanceSheets);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function getAttendanceReportsData($request)
    {
        try {
            // $employeeId = $request->query('employee', null);
            // $fromDate = $request->query('fromDate', null);
            $reportType = $request->query('reportType', null);
            $entityId = $request->query('entityId', null);
            $reportDate = $request->query('reportDate', null);
            $pageNo = $request->query('pageNo', null);
            $pageCount = $request->query('pageCount', null);
            $dataType = $request->query('dataType', null);
            $columnHeadersData = $request->query('columnHeaders', null);
            $columnHeaders = json_decode($columnHeadersData);
            $sort = null;
            $fromDate = $reportDate;
            $toDate = $reportDate;

            if ($reportType == 'dailyAttendance' || $reportType == 'dailyAbsentWithoutLeave' || $reportType == 'dailyAbsentWithLeave' || $reportType == 'dailyLateHours') {
                $attendanceSheets = $this->getAttendanceReportData(null, $fromDate, $toDate, $pageNo, $pageCount, $sort, $entityId, $dataType, $reportType);
            } else if ($reportType == 'dailyInvalidAttendance') {
                $attendanceSheets = $this->getInvalidAttendanceReportData(null, $fromDate, $toDate, $pageNo, $pageCount, $sort, $entityId, $dataType, $reportType);
            } else if ($reportType == 'dailyOT') {
                $attendanceSheets = $this->getDailyOTReportData(null, $fromDate, $toDate, $pageNo, $pageCount, $sort, $entityId, $dataType, $reportType);
            }




            if ($dataType === 'table') {
                return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $attendanceSheets);
            } else {
                
                $headerArray = [];
                $columnMappingDataIndexs = [];
                $cellOneLetter = 'A';
                $currenttLetter = '';
                foreach ($columnHeaders as $key => $columnData) {
                    $columnData = (array) $columnData;
                    if ($columnData['isShowColumn']) {
                        if ($currenttLetter == '') {
                            $currenttLetter = $cellOneLetter;
                        } else {
                            $currenttLetter++;
                        }
                        array_push($headerArray, $columnData['name']);
                        $columnMappingDataIndexs[$columnData['name']] = $columnData['mappedDataIndex'];
                    }
                }   
                $cellRange =  $cellOneLetter.'1:'.$currenttLetter.'1';    
                $report ="dailyAttendance";
                $fileData = $this->downloadAttendanceReport($headerArray, $attendanceSheets->sheets, $cellRange ,$report, $columnMappingDataIndexs);

                return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET_FILE'), $fileData);
            }

            // return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $attendanceSheets);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    private function downloadAttendanceReport($headerArray, $dataSetArray, $headerColumnCellRange ,$report, $columnMappingDataIndexs)
    {
        try {
            $excelData = Excel::download(new AttendanceReportExcelExport($headerArray, $dataSetArray, $headerColumnCellRange,$report, $columnMappingDataIndexs), 'dailyAttendanceReport.xlsx');
            $file = $excelData->getFile()->getPathname();
            $fileData = file_get_contents($file);
            unlink($file);

            return base64_encode($fileData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    

    public function getEmployeeAttendanceSheetData($request)
    {
        try {
            $employeeId = $this->session->getEmployee()->id;

            if (!$employeeId) {
                return $this->error(404, Lang::get('attendanceMessages.basic.ERR_GET'));
            }

            $fromDate = $request->query('fromDate', null);
            $toDate = $request->query('toDate', null);
            $pageNo = $request->query('pageNo', null);
            $pageCount = $request->query('pageCount', null);
            $sort = json_decode($request->query('sort', null));

            $attendanceSheets = $this->getAttendanceSheetData($employeeId, $fromDate, $toDate, $pageNo, $pageCount, $sort, true);

            return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $attendanceSheets);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }



    public function calculateTotalOtCount($fromDate, $toDate, $employeeId) {
        // get current months related summary Ids
        $relateAttendanceRecords = DB::table('attendance_summary')
            ->select('id')
            ->where('employeeId', $employeeId)
            ->where('attendance_summary.date', '>=', $fromDate)
            ->where('attendance_summary.date', '<=', $toDate)
            ->get();

        $currentMonthSumaryIds = [];

        foreach ($relateAttendanceRecords as $attSumKey => $sumRecord) {
            $sumRecord = (array) $sumRecord;
            $currentMonthSumaryIds[] = $sumRecord['id'];
        }
        $totalOtHoursData =  DB::table('attendanceSummaryPayTypeDetail')
            ->leftJoin('payType', 'payType.id', '=', 'attendanceSummaryPayTypeDetail.payTypeId')
            ->where('payType.type', '=', 'OVERTIME')
            ->selectRaw('(sum(attendanceSummaryPayTypeDetail.workedTime)) as otHours')
            ->whereIn('attendanceSummaryPayTypeDetail.summaryId', $currentMonthSumaryIds)->first();

        return $totalOtHoursData->otHours;
       
    
    }

    public function getPostOtRequestAttendanceSheetData($request)
    {
        try {
            $employeeId = $this->session->getEmployee()->id;

            if (!$employeeId) {
                return $this->error(404, Lang::get('attendanceMessages.basic.ERR_GET'));
            }

            $fromDate = $request->query('fromDate', null);
            $toDate = $request->query('toDate', null);
            $preMonthFromDate = $request->query('preMonthFromDate', null);
            $preMonthToDate = $request->query('preMonthToDate', null);
            $pageNo = $request->query('pageNo', null);
            $pageCount = $request->query('pageCount', null);
            $sort = json_decode($request->query('sort', null));


            $attendanceSheets = $this->getAttendanceSheetData($employeeId, $fromDate, $toDate, $pageNo, $pageCount, $sort, true, true);

            //calculate Total OT for current month and previos month
            $currentMonthTotalOtDetails = $this->calculateTotalOtCount($fromDate, $toDate, $employeeId);
            $previousMonthTotalOtDetails = $this->calculateTotalOtCount($preMonthFromDate, $preMonthToDate, $employeeId);

            $attendanceSheets->currentMonthTotalOt = !is_null($currentMonthTotalOtDetails) ? (int)$currentMonthTotalOtDetails : 0;
            $attendanceSheets->previousMonthTotalOt = !is_null($previousMonthTotalOtDetails) ? (int)$previousMonthTotalOtDetails : 0;

            return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $attendanceSheets);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }


    public function getAttendanceDetailsByPostOtRequestId($id)
    {
        try {

            //get related post ot request details
            $relateAttendanceRecords =  DB::table('postOtRequestDetail')
                    ->select('postOtRequestDetail.*', 'workShifts.name as shiftName', 'postOtRequest.currentState')
                    ->leftJoin('workShifts', 'workShifts.id', '=', 'postOtRequestDetail.shiftId')
                    ->leftJoin('postOtRequest', 'postOtRequest.id', '=', 'postOtRequestDetail.postOtRequestId')
                    ->where('postOtRequestDetail.postOtRequestId', (int)$id)
                    ->get();
            
            $attendanceSheetsArray = [];
            $requestState = null;
            foreach ($relateAttendanceRecords as $key => $value) {


                $dateArr = explode(' ',$value->actualIn);
                $date = $dateArr[0];

                $outDateArr = explode(' ',$value->actualOut);
                $outDate = $outDateArr[0];

                if (is_null($requestState)) {
                    $requestState = $value->currentState;
                }

                $attendanceItem = new stdClass();
                $attendanceItem->id = $value->id;
                $attendanceItem->date = $date;
                $attendanceItem->outDate = $outDate;
                $attendanceItem->requestState = $value->currentState;
                // $attendanceItem->employeeIdNo = $attendance->employeeIdNo;
                // $attendanceItem->name = $attendance->employeeName;
                $attendanceItem->shiftId = $value->shiftId;
                $attendanceItem->summaryId = $value->summaryId;
                $attendanceItem->shift = !is_null($value->shiftName) ? $value->shiftName : null;
                $attendanceItem->leave = [];
                $attendanceItem->day = new stdClass();
                $attendanceItem->day->isWorked = !is_null($value->actualIn) ? true : false;
                $attendanceItem->in = new stdClass();
                $attendanceItem->in->time = $value->actualIn ? date('h:i A', strtotime($value->actualIn)) : null;
                $attendanceItem->in->date = $date;
                $attendanceItem->out = new stdClass();
                $attendanceItem->out->time = $value->actualOut ? date('h:i A', strtotime($value->actualOut)) : null;
                $attendanceItem->out->date = $outDate;
                $attendanceItem->incompleUpdate = false;
                $attendanceItem->duration = new stdClass();
                $attendanceItem->duration->worked = gmdate("H:i", $value->workTime * 60);
                $attendanceItem->duration->workedMin = $value->workTime ? $value->workTime : 0;
                $attendanceItem->otData = json_decode($value->otDetails);
                $attendanceItem->approveUserComment = null;
                $attendanceItem->requestedEmployeeComment = $value->requestedEmployeeComment;
                $attendanceItem->approveUserCommentList = !empty($value->approveUserComment) ? json_decode($value->approveUserComment) : [];
                
                array_push($attendanceSheetsArray, $attendanceItem);
            }


            $responce = new stdClass();
            $responce->count = sizeof($attendanceSheetsArray);
            $responce->sheets = $attendanceSheetsArray;
            $responce->isMaintainOt = true;
            $responce->requestState = $requestState;

            return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'),  $responce);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    /**
     * Following function return the ot calculate ability of requested employee
     *
     * @return int | String | array
     *
     * Sample output:
     * $statusCode => 200,
     *  */
    public function checkOtAccessability()
    {
        $employeeId = $this->session->getEmployee()->id;

        if (!$employeeId) {
            return $this->error(404, Lang::get('attendanceMessages.basic.ERR_GET'));
        }

        $companyMantainOtState = $this->getConfigValue('over_time_maintain_state');

        $employee = DB::table('employee')->where('id', $employeeId)->get('isOTAllowed')->first();
        $employeeOtState = $employee->isOTAllowed;

        $responce = [
            'isMaintainOt' => ($companyMantainOtState && $employeeOtState) ? true : false
        ];

        return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'),  $responce);

    }


    /**
     * Following function return the ot calculate ability of requested employee
     *
     * @return int | String | array
     *
     * Sample output:
     * $statusCode => 200,
     *  */
    public function checkOtAccessabilityForCompany()
    {
        $companyMantainOtState = $this->getConfigValue('over_time_maintain_state');

        $response = [
            'isMaintainOt' => ($companyMantainOtState) ? true : false
        ];

        return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'),  $response);

    }
    /**
     * Following function retrive the all break details related to attendance summery record
     *
     * @return int | String | array
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Break details retrive sucessfully!",
     *  */
    public function getAttendanceRelatedBreaks($request)
    {
        try {

            $summeryId = $request->query('summeryId', null);
            if (!$summeryId) {
                return $this->error(404, Lang::get('attendanceMessages.basic.ERR_GET'));
            }

            $processedBreaks = [];
            $attendanceRecords = DB::table('attendance')->where('attendance.summaryId', $summeryId)->get();

            if (!is_null($attendanceRecords)) {
                foreach ($attendanceRecords as $key => $attendance) {
                    $relatedBreakes = DB::table('break')->where('attendanceId', $attendance->id)->get();
                    if (!is_null($relatedBreakes)) {
                        foreach ($relatedBreakes as $breakKey => $break) {
                            $break = (array) $break;

                            $breakInArray = explode(" ", $break['in']);
                            $breakOutArray = explode(" ", $break['out']);

                            $breakInTime = $breakInArray[1];
                            $breakOutTime = $breakOutArray[1];
                            $breakOutDate = $breakOutArray[0];
                            $breakInDate = $breakInArray[0];

                            $processedBreaks[] = [
                                'id' => $break['id'],
                                'breakInDate' => $breakInDate,
                                'breakInTime' => $breakInTime,
                                'breakOutDate' => $breakOutDate,
                                'breakOutTime' => $breakOutTime,
                            ];
                        }
                    }
                }
            }

            return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $processedBreaks);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function getAdminAttendanceSheetData($request)
    {
        try {
            $employeeId = $request->query('employee', null);
            $fromDate = $request->query('fromDate', null);
            $toDate = $request->query('toDate', null);
            $pageNo = $request->query('pageNo', null);
            $pageCount = $request->query('pageCount', null);
            $sort = json_decode($request->query('sort', null));

            $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();

            if (!is_null($employeeId) && !in_array($employeeId, $permittedEmployeeIds)) {
                return $this->error(403, Lang::get('attendanceMessages.basic.ERR_NOT_PERMITTED'), null);
            }

            $attendanceSheets = $this->getAttendanceSheetData($employeeId, $fromDate, $toDate, $pageNo, $pageCount, $sort);

            return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $attendanceSheets);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }


    /**
     * Following function retrive invalid attendance records.
     *
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Invalid Attendance Retrive Successfully",
     *      $data => [{"shiftId": 30, ...} ]
     *
     */
    public function getInvalidAttendanceData($request)
    {
        try {
            $employeeId = $request->query('employee', null);
            $fromDate = $request->query('fromDate', null);
            $toDate = $request->query('toDate', null);
            $pageNo = $request->query('pageNo', null);
            $pageCount = $request->query('pageCount', null);
            $sort = json_decode($request->query('sort', null));

            $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();

            if (!is_null($employeeId) && !in_array($employeeId, $permittedEmployeeIds)) {
                return $this->error(403, Lang::get('attendanceMessages.basic.ERR_NOT_PERMITTED'), null);
            }

            $attendanceSheets = $this->getInvalidAttendanceSheetData($employeeId, $fromDate, $toDate, $pageNo, $pageCount, $sort);

            return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $attendanceSheets);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    /**
     * Following function retrive the summery recode of perticular employee shift date
     *
     * @return | array
     *
     * Sample output:[id=> 15, date => '2022-10-17']
     *  */
    private function getAttendanceDateData ($employeeId, $attendanceDate) 
    {
        try {
            $whereQuery = '';
            $whereQuery = "WHERE attendance_summary.employeeId = " . $employeeId . " AND  attendance_summary.date = '" . $attendanceDate . "'";

            $query = "
            SELECT
                attendance_summary.actualIn as firstIn,
                attendance_summary.actualOut as lastOut,
                attendance_summary.workTime as totalWorked,
                attendance_summary.breakTime as totalBreaks,
                attendance_summary.id,
                attendance_summary.date,
                attendance_summary.timeZone,
                attendance_summary.shiftId,
                attendance_summary.isPresent,
                attendance_summary.isFullDayLeave,
                attendance_summary.isHalfDayLeave,
                attendance_summary.isShortLeave,
                workCalendarDayType.name,
                workCalendarDayType.typeColor,
                workShifts.name as shiftName,
                employee.id as employeeIdNo,
                CONCAT(employee.firstName, ' ', employee.lastName) as employeeName,
                attendance_summary.lateIn as lateTime,
                attendance_summary.earlyOut as earlyTime,
                time_change_requests.id as requestedTimeChangeId,
                workPattern.name as workPatternName
            FROM attendance_summary
                LEFT JOIN employee on attendance_summary.employeeId = employee.id
            	LEFT JOIN workShifts ON workShifts.id = attendance_summary.shiftId
                LEFT JOIN time_change_requests on attendance_summary.id = time_change_requests.summaryId AND time_change_requests.type = 0
                LEFT JOIN workPatternWeekDay ON workShifts.id = workPatternWeekDay.workShiftId
                LEFT JOIN workPatternWeek ON workPatternWeekDay.workPatternWeekId = workPatternWeek.id
                LEFT JOIN workPattern ON workPatternWeek.workPatternId = workPattern.id
                LEFT JOIN workCalendarDayType ON attendance_summary.dayTypeId = workCalendarDayType.id
            {$whereQuery}
            GROUP BY attendance_summary.id
            ;
            ";

            $attendanceSheet = DB::select($query);
            $isMaintainOt = $this->getConfigValue('over_time_maintain_state');
            $attendance = (sizeof($attendanceSheet) > 0) ? $attendanceSheet[0] : null;
            $leaveData = null;

            // if ($attendance->isFullDayLeave || $attendance->isHalfDayLeave ||  $attendance->isShortLeave) {
            if ($attendance->isFullDayLeave || $attendance->isHalfDayLeave) {
                $leave = $this->getEmployeeApprovedLeave($attendance->employeeIdNo, $attendance->date);

                if (sizeof($leave) > 0) {
                    $leaveData = (array)$leave[0];
                }
            }

            $leaves = [];
            if (!is_null($leaveData)) {
                if ($attendance->isFullDayLeave) {
                    $leave = new stdClass();
                    $leave->name = 'Full Day';
                    $leave->typeString = $leaveData['leaveTypeName'] . ' (' . $leaveData['entitlePortion'] . ')';
                    $leave->entitlePortion = $leaveData['entitlePortion'];
                    $leave->color = 'Red';
                    array_push($leaves, $leave);
                }
                if ($attendance->isHalfDayLeave) {
                    $leave = new stdClass();
                    $leave->typeString = $leaveData['leaveTypeName'] . ' (' . $leaveData['entitlePortion'] . ')';
                    $leave->entitlePortion = $leaveData['entitlePortion'];
                    $leave->name = 'Half Day';
                    $leave->color = 'Orange';
                    array_push($leaves, $leave);
                }
            }

            // if ($attendance->isShortLeave) {
            //     $sortLeaveType = null;
            //     if ($leaveData['leavePeriodType'] == 'IN_SHORT_LEAVE') {
            //         $sortLeaveType = 'In';
            //     } else {
            //         $sortLeaveType = 'Out';
            //     }

            //     $leave = new stdClass();
            //     $leave->typeString = $leaveData['leaveTypeName'] . ' (' . $sortLeaveType . ')';
            //     $leave->name = 'Short';
            //     $leave->color = 'Yellow';
            //     array_push($leaves, $leave);
            // }

            $inDate = $attendance->firstIn ? date('Y-m-d', strtotime($attendance->firstIn)) : null;
            $outDate = $attendance->lastOut ? date('Y-m-d', strtotime($attendance->lastOut)) : null;
            $isDifferentOutDate = $attendance->lastOut && date('Y-m-d', strtotime($attendance->date)) !== date('Y-m-d', strtotime($attendance->lastOut)) ? true : false;

            $totalLate = '00:00';
            if ($attendance->lateTime && $attendance->earlyTime) {
                $totalLate = gmdate("H:i", ($attendance->earlyTime * 60 + $attendance->lateTime * 60));
            } elseif ($attendance->lateTime && !$attendance->earlyTime) {
                $totalLate = gmdate("H:i", ($attendance->lateTime * 60));
            } elseif (!$attendance->lateTime && $attendance->earlyTime) {
                $totalLate = gmdate("H:i", ($attendance->earlyTime * 60));
            }

            $attendanceItem = new stdClass();
            $attendanceItem->id = $attendance->id;
            $attendanceItem->date = $attendance->date;
            $attendanceItem->outDate = (!empty($outDate)) ? $outDate : $attendance->date;
            $attendanceItem->employeeIdNo = $attendance->employeeIdNo;
            $attendanceItem->name = $attendance->employeeName;
            $attendanceItem->shiftId = $attendance->shiftId;
            $attendanceItem->summaryId = $attendance->id;
            $attendanceItem->timeZone = $attendance->timeZone;
            $attendanceItem->shift = is_null($attendance->shiftName) ? $attendance->workPatternName : $attendance->shiftName;
            $attendanceItem->requestedTimeChangeId = $attendance->requestedTimeChangeId;
            $attendanceItem->leave = $leaves;
            $attendanceItem->day = new stdClass();
            $attendanceItem->day->isWorked = $attendance->isPresent;
            $attendanceItem->day->dayType = $attendance->name;
            $attendanceItem->day->dayTypeColor = $attendance->typeColor;
            $attendanceItem->in = new stdClass();
            $attendanceItem->in->time = $attendance->firstIn ? date('h:i A', strtotime($attendance->firstIn)) : null;
            $attendanceItem->in->late = $attendance->lateTime ? gmdate("H:i", $attendance->lateTime * 60) : null;
            $attendanceItem->in->date = $inDate;
            $attendanceItem->out = new stdClass();
            $attendanceItem->out->time = $attendance->lastOut ? date('h:i A', strtotime($attendance->lastOut)) : null;
            $attendanceItem->out->early = $attendance->earlyTime ?  gmdate("H:i", $attendance->earlyTime * 60): null;
            $attendanceItem->out->date = $outDate;
            $attendanceItem->totalLate = $totalLate;
            $attendanceItem->out->isDifferentOutDate = $isDifferentOutDate;
            $attendanceItem->incompleUpdate = false;
            $attendanceItem->duration = new stdClass();
            $attendanceItem->duration->worked = gmdate("H:i", $attendance->totalWorked * 60);
            $attendanceItem->duration->workedMin = $attendance->totalWorked ? $attendance->totalWorked : 0;
            $attendanceItem->duration->breaks = $attendance->totalBreaks ? gmdate("H:i", $attendance->totalBreaks * 60) : null;

            $calculatedTotalOtMins = 0;
            //get related ot details
            if ($isMaintainOt) {
                $relateOtRecords =  DB::table('attendanceSummaryPayTypeDetail')
                    ->leftJoin('payType', 'payType.id', '=', 'attendanceSummaryPayTypeDetail.payTypeId')
                    ->where('payType.type', '=', 'OVERTIME')
                    ->where('attendanceSummaryPayTypeDetail.summaryId', '=', $attendance->id)->get(['attendanceSummaryPayTypeDetail.id as attendanceSummaryPayTypeDetailId', 'payType.name', 'attendanceSummaryPayTypeDetail.workedTime', 'payType.code', 'attendanceSummaryPayTypeDetail.approvedWorkTime']);

                $totalOtHoursData =  DB::table('attendanceSummaryPayTypeDetail')
                    ->leftJoin('payType', 'payType.id', '=', 'attendanceSummaryPayTypeDetail.payTypeId')
                    ->where('payType.type', '=', 'OVERTIME')
                    ->selectRaw('(sum(attendanceSummaryPayTypeDetail.workedTime)) as otHours')
                    ->groupBy('attendanceSummaryPayTypeDetail.summaryId')
                    ->where('attendanceSummaryPayTypeDetail.summaryId', '=', $attendance->id)->first();

                if (!empty($totalOtHoursData)) {
                    $totalOtHours = gmdate("H:i", $totalOtHoursData->otHours * 60);
                    $calculatedTotalOtMins = $totalOtHoursData->otHours;
                } else {
                    $totalOtHours = gmdate("H:i", 0 * 60);
                }


                $otDetails = [];
                $approvedOtDetails = [];
                $requestedOtDetails = [];
                $totalApprovedOtHours = 0;
                $reason = null;

                $postOtRequestRecord = null;
                foreach ($relateOtRecords as $key => $otData) {
                    $otDetails[$otData->code] = gmdate("H:i", $otData->workedTime * 60);
                    $approvedOtDetails[$otData->code] = gmdate("H:i", $otData->approvedWorkTime * 60);
                    $totalApprovedOtHours += $otData->approvedWorkTime;
                    $requestedOtDetails[$otData->code] = gmdate("H:i", $otData->workedTime * 60);

                }

                $attendanceItem->otData =  new stdClass();
                $attendanceItem->otData->totalOtHours = $totalOtHours;
                $attendanceItem->otData->otDetails = $otDetails;
                $attendanceItem->otData->approvedOtDetails = $approvedOtDetails;
                $attendanceItem->otData->totalApprovedOtHours = gmdate("H:i", $totalApprovedOtHours * 60);;
                $attendanceItem->otData->requestedOtDetails = $requestedOtDetails;
                $attendanceItem->reason = $reason;
            }

            return $attendanceItem;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }

    }

    private function getRelatedDayTypesByEmployee($empIdArray) {
        //get related calenders
        $relatedCalanderIds =  DB::table('employeeJob')
        ->whereIn('employeeId', $empIdArray)
        ->groupBy('calendarId')
        ->pluck('calendarId');

        $calanderRelatedDayTypes =  DB::table('workCalendarDateNames')
                ->leftJoin('workCalendarDayType', 'workCalendarDayType.id', '=', 'workCalendarDateNames.workCalendarDayTypeId')
                ->whereIn('calendarId', $relatedCalanderIds)
                ->groupBy('workCalendarDayTypeId');

        $relatedMainDayTypesIds=  $calanderRelatedDayTypes->pluck('workCalendarDayTypeId');

        //get other related Daytypes from special days
        $specialDayRelatedDayTypesIds = DB::table('workCalendarSpecialDays')
                ->leftJoin('workCalendarDayType', 'workCalendarDayType.id', '=', 'workCalendarSpecialDays.workCalendarDayTypeId')
                ->whereIn('calendarId', $relatedCalanderIds)
                ->whereNotIn('workCalendarDayTypeId', $relatedMainDayTypesIds)
                ->groupBy('workCalendarDayTypeId')
                ->pluck('workCalendarDayTypeId');

        $ids = [];
        if (sizeof($relatedMainDayTypesIds) > 0) {
            foreach ($relatedMainDayTypesIds as $key1 => $value1) {
                array_push($ids, $value1);
            }
        }

        if (sizeof($specialDayRelatedDayTypesIds) > 0) {
            foreach ($specialDayRelatedDayTypesIds as $key2 => $value2) {
                array_push($ids, $value2);
            }
        }

        //get dayTypes
        $dayTypesData = DB::table('workCalendarDayType')
            ->whereIn('id', $ids)
            ->get(['name','typeColor']);

        return $dayTypesData;
    }

    private function getAttendanceSheetData($employeeId, $fromDate, $toDate, $pageNo, $pageCount, $sort, $isFromEmployee = false, $isForPostOTRequest = false)
    {
        try {
            $whereQuery = '';
            $paginationQuery = '';
            $orderQuery = '';
            $whereInQuery = '';

            $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();

            if (is_null($employeeId) && empty($permittedEmployeeIds)) {
                return null;
            }


            $empIdArray = [];
            if (!is_null($employeeId)) {
                if (!is_array($employeeId)) {
                    array_push($empIdArray, $employeeId);
                } else {
                    foreach ($employeeId as $ekey => $empIDElement) {
                        array_push($empIdArray, $empIDElement);
                    }
                }
            } else {
                $empIdArray = $permittedEmployeeIds;
            }

            $relatedDayTypes = $this->getRelatedDayTypesByEmployee($empIdArray);


            if (!empty($employeeId) && is_array($employeeId)) {
                $permittedEmployeeIds =  $employeeId;
                $employeeId = null;
            }

            if (count($permittedEmployeeIds) > 0) {
                $whereInQuery = "WHERE attendance_summary.employeeId IN (" . implode(",", $permittedEmployeeIds) . ")";
            }

            if ($employeeId && $fromDate) {
                $whereQuery = "WHERE attendance_summary.employeeId = " . $employeeId . " AND  attendance_summary.date >= '" . $fromDate . "' AND attendance_summary.date <= '" . $toDate . "'";
            } else if ($employeeId) {
                $whereQuery = "WHERE attendance_summary.employeeId = " . $employeeId;
            } else if ($fromDate) {
                $clause = "attendance_summary.date >= '" . $fromDate . "' AND attendance_summary.date <= '" . $toDate . "'";
                $whereQuery = empty($whereInQuery) ? "WHERE " . $clause : $whereInQuery . " AND " . $clause;
            } else {
                $whereQuery = $whereInQuery;
            }

            if ($pageNo && $pageCount) {
                $skip = ($pageNo - 1) * $pageCount;
                $paginationQuery = "LIMIT " . $skip . ", " . $pageCount;
            }

            if ($sort->name === 'name') {
                $orderQuery = "ORDER BY employeeName " . $sort->order . ", attendance_summary.date ASC";
            } else if ($sort->name === 'date') {
                $orderQuery = "ORDER BY attendance_summary.date " . $sort->order;
            }

            $queryCount = "
            SELECT
                COUNT(*) as dataCount
            FROM attendance_summary
            LEFT JOIN employee on attendance_summary.employeeId = employee.id
            LEFT JOIN workShifts ON workShifts.id = attendance_summary.shiftId
            LEFT JOIN time_change_requests on attendance_summary.id = time_change_requests.summaryId AND time_change_requests.type = 0
            {$whereQuery}
            GROUP BY attendance_summary.id
            ;
            ";

            $query = "
            SELECT
                attendance_summary.actualIn as firstIn,
                attendance_summary.actualOut as lastOut,
                attendance_summary.workTime as totalWorked,
                attendance_summary.breakTime as totalBreaks,
                attendance_summary.id,
                attendance_summary.date,
                attendance_summary.timeZone,
                attendance_summary.shiftId,
                attendance_summary.dayTypeId,
                attendance_summary.isPresent,
                attendance_summary.isExpectedToPresent,
                attendance_summary.isFullDayLeave,
                attendance_summary.isHalfDayLeave,
                attendance_summary.isShortLeave,
                workCalendarDayType.name,
                workCalendarDayType.typeColor,
                workShifts.name as shiftName,
                employee.id as employeeIdNo,
                CONCAT(employee.firstName, ' ', employee.lastName) as employeeName,
                attendance_summary.lateIn as lateTime,
                attendance_summary.earlyOut as earlyTime,
                time_change_requests.id as requestedTimeChangeId,
                workPattern.name as workPatternName
            FROM attendance_summary
                LEFT JOIN employee on attendance_summary.employeeId = employee.id
            	LEFT JOIN workShifts ON workShifts.id = attendance_summary.shiftId
                LEFT JOIN time_change_requests on attendance_summary.id = time_change_requests.summaryId AND time_change_requests.type = 0
                LEFT JOIN workPatternWeekDay ON workShifts.id = workPatternWeekDay.workShiftId
                LEFT JOIN workPatternWeek ON workPatternWeekDay.workPatternWeekId = workPatternWeek.id
                LEFT JOIN workPattern ON workPatternWeek.workPatternId = workPattern.id
                LEFT JOIN workCalendarDayType ON attendance_summary.dayTypeId = workCalendarDayType.id
            {$whereQuery}
            GROUP BY attendance_summary.id
            {$orderQuery}
            {$paginationQuery}
            ;
            ";

            $attendanceCount = DB::select($queryCount);
            $attendanceSheets = DB::select($query);
            $attendanceSheetsArray = [];
            $isMaintainOt = $this->getConfigValue('over_time_maintain_state');
            $sugessionRequireDates = [];

            foreach ($attendanceSheets as $key => $attendance) {
                $leaveData = [];
                // if ($attendance->isFullDayLeave || $attendance->isHalfDayLeave ||  $attendance->isShortLeave) {
                if ($attendance->isFullDayLeave || $attendance->isHalfDayLeave) {
                    $leave = $this->getEmployeeApprovedLeave($attendance->employeeIdNo, $attendance->date);

                    if (sizeof($leave) > 0) {
                        $leaveData = (array)$leave[0];
                    }
                }
                $leaves = [];
                if (sizeof($leaveData) > 0) {
                    if ($attendance->isFullDayLeave) {
                        $leave = new stdClass();
                        $leave->name = 'Full Day';
                        $leave->typeString = $leaveData['leaveTypeName'] . ' (' . $leaveData['entitlePortion'] . ')';
                        $leave->entitlePortion = $leaveData['entitlePortion'];
                        $leave->color = 'Red';
                        array_push($leaves, $leave);
                    }
                    if ($attendance->isHalfDayLeave) {
                        $leave = new stdClass();
                        $leave->typeString = $leaveData['leaveTypeName'] . ' (' . $leaveData['entitlePortion'] . ')';
                        $leave->entitlePortion = $leaveData['entitlePortion'];
                        $leave->name = 'Half Day';
                        $leave->color = 'Orange';
                        array_push($leaves, $leave);
                    }
                }
                // if ($attendance->isShortLeave) {
                //     $sortLeaveType = null;
                //     if ($leaveData['leavePeriodType'] == 'IN_SHORT_LEAVE') {
                //         $sortLeaveType = 'In';
                //     } else {
                //         $sortLeaveType = 'Out';
                //     }

                //     $leave = new stdClass();
                //     $leave->typeString = $leaveData['leaveTypeName'] . ' (' . $sortLeaveType . ')';
                //     $leave->name = 'Short';
                //     $leave->color = 'Yellow';
                //     array_push($leaves, $leave);
                // }

                $inDate = $attendance->firstIn ? date('Y-m-d', strtotime($attendance->firstIn)) : null;
                $outDate = $attendance->lastOut ? date('Y-m-d', strtotime($attendance->lastOut)) : null;
                $isDifferentOutDate = $attendance->lastOut && date('Y-m-d', strtotime($attendance->date)) !== date('Y-m-d', strtotime($attendance->lastOut)) ? true : false;

                $totalLate = '00:00';
                if ($attendance->lateTime && $attendance->earlyTime) {
                    $totalLate = gmdate("H:i", ($attendance->earlyTime * 60 + $attendance->lateTime * 60));
                } elseif ($attendance->lateTime && !$attendance->earlyTime) {
                    $totalLate = gmdate("H:i", ($attendance->lateTime * 60));
                } elseif (!$attendance->lateTime && $attendance->earlyTime) {
                    $totalLate = gmdate("H:i", ($attendance->earlyTime * 60));
                }

                $isBehaveAsNonWorkingDay = false;
                if (!is_null($attendance->shiftId) && !is_null($attendance->dayTypeId)) {
                    //get related workshift day type 
                    $relateWorkShiftDayTypeRecord = (array) DB::table('workShiftDayType')
                            ->where('workShiftId', $attendance->shiftId)
                            ->where('dayTypeId', $attendance->dayTypeId)->first();
                    $isBehaveAsNonWorkingDay = $relateWorkShiftDayTypeRecord['isBehaveAsNonWorkingDay'];
                }

                $attendanceItem = new stdClass();
                $attendanceItem->id = $attendance->id;
                $attendanceItem->date = $attendance->date;
                $attendanceItem->isExpectedToPresent = $attendance->isExpectedToPresent;
                $attendanceItem->outDate = (!empty($outDate)) ? $outDate : $attendance->date;
                $attendanceItem->employeeIdNo = $attendance->employeeIdNo;
                $attendanceItem->name = $attendance->employeeName;
                $attendanceItem->shiftId = $attendance->shiftId;
                $attendanceItem->summaryId = $attendance->id;
                $attendanceItem->timeZone = $attendance->timeZone;
                $attendanceItem->shift = is_null($attendance->shiftName) ? $attendance->workPatternName : $attendance->shiftName;
                $attendanceItem->requestedTimeChangeId = $attendance->requestedTimeChangeId;
                $attendanceItem->leave = $leaves;
                $attendanceItem->day = new stdClass();
                $attendanceItem->day->isWorked = $attendance->isPresent;
                $attendanceItem->day->dayType = $attendance->name;
                $attendanceItem->day->dayTypeColor = $attendance->typeColor;
                $attendanceItem->day->isBehaveAsNonWorkingDay = $isBehaveAsNonWorkingDay;
                $attendanceItem->in = new stdClass();
                $attendanceItem->in->time = $attendance->firstIn ? date('h:i A', strtotime($attendance->firstIn)) : null;
                $attendanceItem->in->late = $attendance->lateTime ? gmdate("H:i", $attendance->lateTime * 60) : null;
                $attendanceItem->in->date = $inDate;
                $attendanceItem->out = new stdClass();
                $attendanceItem->out->time = $attendance->lastOut ? date('h:i A', strtotime($attendance->lastOut)) : null;
                $attendanceItem->out->early = $attendance->earlyTime ?  gmdate("H:i", $attendance->earlyTime * 60): null;
                $attendanceItem->out->date = $outDate;
                $attendanceItem->totalLate = $totalLate;
                $attendanceItem->out->isDifferentOutDate = $isDifferentOutDate;
                $attendanceItem->incompleUpdate = false;
                $attendanceItem->duration = new stdClass();
                $attendanceItem->duration->worked = gmdate("H:i", $attendance->totalWorked * 60);
                $attendanceItem->duration->workedMin = $attendance->totalWorked ? $attendance->totalWorked : 0;
                $attendanceItem->duration->breaks = $attendance->totalBreaks ? gmdate("H:i", $attendance->totalBreaks * 60) : null;

                //get sugesstions for time change  requests and leaves
                if ($isFromEmployee) {
                    if ($attendance->isExpectedToPresent && (empty($attendanceItem->in->time) || empty($attendanceItem->out->time)) && empty($attendanceItem->requestedTimeChangeId)) {
                        $sugessionRequireDates[] = Carbon::parse($attendance->date)->format('jS');
                    }
                }

                $calculatedTotalOtMins = 0;
                $isRecordApprove = false;
                //get related ot details
                if ($isMaintainOt) {
                    $relateOtRecords =  DB::table('attendanceSummaryPayTypeDetail')
                        ->leftJoin('payType', 'payType.id', '=', 'attendanceSummaryPayTypeDetail.payTypeId')
                        ->where('payType.type', '=', 'OVERTIME')
                        ->where('attendanceSummaryPayTypeDetail.summaryId', '=', $attendance->id)->get(['attendanceSummaryPayTypeDetail.id as attendanceSummaryPayTypeDetailId', 'payType.name', 'attendanceSummaryPayTypeDetail.workedTime', 'payType.code', 'attendanceSummaryPayTypeDetail.approvedWorkTime']);

                    $totalOtHoursData =  DB::table('attendanceSummaryPayTypeDetail')
                        ->leftJoin('payType', 'payType.id', '=', 'attendanceSummaryPayTypeDetail.payTypeId')
                        ->where('payType.type', '=', 'OVERTIME')
                        ->selectRaw('(sum(attendanceSummaryPayTypeDetail.workedTime)) as otHours')
                        ->groupBy('attendanceSummaryPayTypeDetail.summaryId')
                        ->where('attendanceSummaryPayTypeDetail.summaryId', '=', $attendance->id)->first();

                    if (!empty($totalOtHoursData)) {
                        $totalOtHours = gmdate("H:i", $totalOtHoursData->otHours * 60);
                        $calculatedTotalOtMins = $totalOtHoursData->otHours;
                    } else {
                        $totalOtHours = gmdate("H:i", 0 * 60);
                    }

                    $otDetails = [];
                    $approvedOtDetails = [];
                    $requestedOtDetails = [];
                    $totalApprovedOtHours = 0;
                    $reason = null;

                    $postOtRequestRecord = null;
                    $postOtRequestRecord =  DB::table('postOtRequestDetail')
                        ->where('summaryId', '=', $attendance->id)
                        ->whereIn('status', ['PENDING', 'APPROVED'])
                        ->first();

                    $attendanceItem->isApprovedOtAttendance  = (!is_null($postOtRequestRecord)) ? true : false;

                    $isRecordApprove = (!is_null($postOtRequestRecord) && $postOtRequestRecord->status == 'APPROVED') ? true : false;

                    if ($isForPostOTRequest) { 
                        //check whether ot is approved status for this attendance record
                        $otApprovedStatus = 'OPEN';
                        if (!is_null($postOtRequestRecord)) {
                            $otApprovedStatus = $postOtRequestRecord->status;
                            $reason = $postOtRequestRecord->requestedEmployeeComment;
                        }
                        $attendanceItem->otApprovedStatus = $otApprovedStatus;
                        
                    }

                    foreach ($relateOtRecords as $key => $otData) {
                        $otDetails[$otData->code] = gmdate("H:i", $otData->workedTime * 60);
                        $approvedOtDetails[$otData->code] = gmdate("H:i", $otData->approvedWorkTime * 60);
                        $totalApprovedOtHours += $otData->approvedWorkTime;
 
                        if (!is_null($postOtRequestRecord)) {
                            $ots = json_decode($postOtRequestRecord->otDetails);
                            $ots = (array) $ots;
                            $ots['requestedOtDetails'] = (array)$ots['requestedOtDetails'];
                            $timeArr = explode(':', $ots['requestedOtDetails'][$otData->code]);
                            $hoursIntoMin =  !empty($timeArr[0]) ? (int)$timeArr[0] * 60: 0;
                            $directMin = !empty($timeArr[1]) ?  (int)$timeArr[1] : 0;
                            $totolOtTime = $hoursIntoMin + $directMin;

                            $requestedOtDetails[$otData->code] = gmdate("H:i", $totolOtTime * 60);
                        } else {

                            $requestedOtDetails[$otData->code] = gmdate("H:i", $otData->workedTime * 60);
                        }

                    }

                    $attendanceItem->otData =  new stdClass();
                    $attendanceItem->otData->totalOtHours = $totalOtHours;
                    $attendanceItem->otData->otDetails = $otDetails;
                    $attendanceItem->otData->approvedOtDetails = $approvedOtDetails;
                    $attendanceItem->otData->totalApprovedOtHours = gmdate("H:i", $totalApprovedOtHours * 60);;
                    $attendanceItem->otData->requestedOtDetails = $requestedOtDetails;
                    $attendanceItem->reason = $reason;
                }
                if (!$isForPostOTRequest) {
                    array_push($attendanceSheetsArray, $attendanceItem);
                } else {

                    //check has any pending time change requests
                    $pendingRequests = $this->store->getFacade()::table('time_change_requests')
                        ->leftJoin('workflowInstance', 'workflowInstance.id', '=', 'time_change_requests.workflowInstanceId')
                        ->where('time_change_requests.summaryId', $attendance->id)
                        ->where('workflowInstance.currentStateId', 1)
                        ->first();

                    if ($calculatedTotalOtMins > 0 && is_null($pendingRequests) && !$isRecordApprove) {
                        array_push($attendanceSheetsArray, $attendanceItem);
                    }
                }
            }

            $suggesionParagraph = null;
            if ($isFromEmployee && sizeof($sugessionRequireDates) > 0) {
                $sugesstionString = implode(', ',$sugessionRequireDates);
                $suggesionParagraph = 'You have incompleted records on '.$sugesstionString.'. Either apply for a leave or request for time change.';
            }

            $responce = new stdClass();
            $responce->count = (!$isForPostOTRequest) ?  count($attendanceCount) : sizeof($attendanceSheetsArray);
            $responce->sheets = $attendanceSheetsArray;
            $responce->isMaintainOt = $isMaintainOt;
            $responce->relatedDayTypes = $relatedDayTypes;
            $responce->suggesionParagraph = $suggesionParagraph;

            return $responce;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    /**
     * Following function retrive processed invalid attendance records.
     *
     *
     * Sample output: $data => ['sheets' => [{}]]
     *
     */
    private function getInvalidAttendanceSheetData($employeeId, $fromDate, $toDate, $pageNo, $pageCount, $sort, $isFromEmployee = false)
    {
        try {
            $whereQuery = '';
            $paginationQuery = '';
            $orderQuery = '';
            $whereInQuery = '';

            $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();

            if (is_null($employeeId) && empty($permittedEmployeeIds)) {
                return null;
            }

            if (count($permittedEmployeeIds) > 0) {
                $whereInQuery = "WHERE attendance_summary.employeeId IN (" . implode(",", $permittedEmployeeIds) . ")";
            }

            if ($employeeId && $fromDate) {
                $whereQuery = "WHERE attendance_summary.employeeId = " . $employeeId . " AND  attendance_summary.date >= '" . $fromDate . "' AND attendance_summary.date <= '" . $toDate . "'";
            } else if ($employeeId) {
                $whereQuery = "WHERE attendance_summary.employeeId = " . $employeeId;
            } else if ($fromDate) {
                $clause = "attendance_summary.date >= '" . $fromDate . "' AND attendance_summary.date <= '" . $toDate . "'";
                $whereQuery = empty($whereInQuery) ? "WHERE " . $clause : $whereInQuery . " AND " . $clause;
            } else {
                $whereQuery = $whereInQuery;
            }

            if ($pageNo && $pageCount) {
                $skip = ($pageNo - 1) * $pageCount;
                $paginationQuery = "LIMIT " . $skip . ", " . $pageCount;
            }

            if ($sort->name === 'name') {
                $orderQuery = "ORDER BY employeeName " . $sort->order . ", attendance_summary.date ASC";
            } else if ($sort->name === 'date') {
                $orderQuery = "ORDER BY attendance_summary.date " . $sort->order;
            }

            $queryCount = "
            SELECT
                COUNT(*) as dataCount
            FROM attendance_summary
            LEFT JOIN employee on attendance_summary.employeeId = employee.id
            LEFT JOIN workShifts ON workShifts.id = attendance_summary.shiftId
            LEFT JOIN time_change_requests on attendance_summary.id = time_change_requests.summaryId AND time_change_requests.type = 0
            {$whereQuery}
            GROUP BY attendance_summary.id
            ;
            ";

            $query = "
            SELECT
                attendance_summary.actualIn as firstIn,
                attendance_summary.actualOut as lastOut,
                attendance_summary.workTime as totalWorked,
                attendance_summary.breakTime as totalBreaks,
                attendance_summary.id,
                attendance_summary.date,
                attendance_summary.timeZone,
                attendance_summary.shiftId,
                attendance_summary.isPresent,
                attendance_summary.isExpectedToPresent,
                attendance_summary.expectedIn,
                attendance_summary.expectedOut,
                attendance_summary.isFullDayLeave,
                attendance_summary.isHalfDayLeave,
                attendance_summary.isShortLeave,
                workCalendarDayType.name,
                workShifts.name as shiftName,
                employee.id as employeeIdNo,
                CONCAT(employee.firstName, ' ', employee.lastName) as employeeName,
                attendance_summary.lateIn as lateTime,
                attendance_summary.earlyOut as earlyTime,
                time_change_requests.id as requestedTimeChangeId,
                workPattern.name as workPatternName
            FROM attendance_summary
                LEFT JOIN employee on attendance_summary.employeeId = employee.id
            	LEFT JOIN workShifts ON workShifts.id = attendance_summary.shiftId
                LEFT JOIN time_change_requests on attendance_summary.id = time_change_requests.summaryId AND time_change_requests.type = 0
                LEFT JOIN workPatternWeekDay ON workShifts.id = workPatternWeekDay.workShiftId
                LEFT JOIN workPatternWeek ON workPatternWeekDay.workPatternWeekId = workPatternWeek.id
                LEFT JOIN workPattern ON workPatternWeek.workPatternId = workPattern.id
                LEFT JOIN workCalendarDayType ON attendance_summary.dayTypeId = workCalendarDayType.id
            {$whereQuery}
            GROUP BY attendance_summary.id
            {$orderQuery}
            {$paginationQuery}
            ;
            ";

            $attendanceCount = DB::select($queryCount);
            $attendanceSheets = DB::select($query);
            $attendanceSheetsArray = [];
            $isMaintainOt = $this->getConfigValue('over_time_maintain_state');
            $sugessionRequireDates = [];

            foreach ($attendanceSheets as $key => $attendance) {
                $leaveData = null;

                $invalidAttendanceCount = DB::table('attendance')->where('summaryId', $attendance->id)->where('employeeId', $attendance->employeeIdNo)->where('date', $attendance->date)->whereNull('out')->count();

                if ($invalidAttendanceCount > 0) {
                    // if ($attendance->isFullDayLeave || $attendance->isHalfDayLeave ||  $attendance->isShortLeave) {
                    if ($attendance->isFullDayLeave || $attendance->isHalfDayLeave) {
                        $leave = $this->getEmployeeLeave($attendance->employeeIdNo, $attendance->date);

                        if (!empty($leave)) {
                            $leaveData = (array)$leave[0];
                        }
                    }

                    //get attendance slot details
                    $attendanceSlotDetails = $this->getAttendanceSlotDetails($attendance->id, $attendance->employeeIdNo, $attendance->date, $attendance->expectedIn, $attendance->expectedOut, $attendance->timeZone);

                    $leaves = [];

                    $inDate = $attendance->firstIn ? date('Y-m-d', strtotime($attendance->firstIn)) : null;
                    $outDate = $attendance->lastOut ? date('Y-m-d', strtotime($attendance->lastOut)) : null;
                    $isDifferentOutDate = $attendance->lastOut && date('Y-m-d', strtotime($attendance->date)) !== date('Y-m-d', strtotime($attendance->lastOut)) ? true : false;

                    $totalLate = '00:00';
                    if ($attendance->lateTime && $attendance->earlyTime) {
                        $totalLate = gmdate("H:i", ($attendance->earlyTime * 60 + $attendance->lateTime * 60));
                    } elseif ($attendance->lateTime && !$attendance->earlyTime) {
                        $totalLate = gmdate("H:i", ($attendance->lateTime * 60));
                    } elseif (!$attendance->lateTime && $attendance->earlyTime) {
                        $totalLate = gmdate("H:i", ($attendance->earlyTime * 60));
                    }

                    $attendanceItem = new stdClass();
                    $attendanceItem->id = $attendance->id;
                    $attendanceItem->date = $attendance->date;
                    $attendanceItem->expectedIn = $attendance->expectedIn;
                    $attendanceItem->expectedOut = $attendance->expectedOut;
                    $attendanceItem->outDate = (!empty($outDate)) ? $outDate : $attendance->date;
                    $attendanceItem->employeeIdNo = $attendance->employeeIdNo;
                    $attendanceItem->name = $attendance->employeeName;
                    $attendanceItem->shiftId = $attendance->shiftId;
                    $attendanceItem->summaryId = $attendance->id;
                    $attendanceItem->timeZone = $attendance->timeZone;
                    $attendanceItem->shift = is_null($attendance->shiftName) ? $attendance->workPatternName : $attendance->shiftName;
                    $attendanceItem->requestedTimeChangeId = $attendance->requestedTimeChangeId;
                    $attendanceItem->leave = $leaves;
                    $attendanceItem->day = new stdClass();
                    $attendanceItem->day->isWorked = $attendance->isPresent;
                    $attendanceItem->day->dayType = $attendance->name;
                    $attendanceItem->in = new stdClass();
                    $attendanceItem->in->time = $attendance->firstIn ? date('h:i A', strtotime($attendance->firstIn)) : null;
                    $attendanceItem->in->late = $attendance->lateTime ? gmdate("H:i", $attendance->lateTime * 60) : null;
                    $attendanceItem->in->date = $inDate;
                    $attendanceItem->out = new stdClass();
                    $attendanceItem->out->time = $attendance->lastOut ? date('h:i A', strtotime($attendance->lastOut)) : null;
                    $attendanceItem->out->early = $attendance->earlyTime ?  gmdate("H:i", $attendance->earlyTime * 60) : null;
                    $attendanceItem->out->date = $outDate;
                    $attendanceItem->slot1 = $attendanceSlotDetails['slot1'];
                    $attendanceItem->slot2 = $attendanceSlotDetails['slot2'];
                    $attendanceItem->totalLate = $totalLate;
                    $attendanceItem->out->isDifferentOutDate = $isDifferentOutDate;
                    $attendanceItem->incompleUpdate = false;
                    $attendanceItem->isChanged = false;
                    $attendanceItem->hasErrors = true;
                    $attendanceItem->duration = new stdClass();
                    $attendanceItem->duration->worked = gmdate("H:i", $attendance->totalWorked * 60);
                    $attendanceItem->duration->breaks = $attendance->totalBreaks ? gmdate("H:i", $attendance->totalBreaks * 60) : null;


                    array_push($attendanceSheetsArray, $attendanceItem);
                }
            }

            $responce = new stdClass();
            $responce->sheets = $attendanceSheetsArray;

            return $responce;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    /**
     * Following function retrive attendance Slot wise details
     *
     *
     * Sample output: ['slot1' => ['attendenceId' => null...], 'slot2' => ['attendenceId' => null ...]]
     *
     */
    private function getAttendanceSlotDetails($summaryId, $employeeId, $date, $expectedIn, $expectedOut, $timeZone) {
        $relatedAttendanceRecords = DB::table('attendance')->where('summaryId', $summaryId)->where('employeeId', $employeeId)->where('date', $date)->orderBy("in", "asc")->limit(2)->get();
        $attendanceSlotDetails = [
            "slot1" => [
                'attendenceId' => null,
                'inDate' => null,
                'inTime' => null,
                'outDate' => null,
                'outTime' => null,
                'inLate' => 0,
                'earlyOut' => 0,
                'slot1InDateTime' => null,
                'slot1OutDateTime' => null
            ],
            'slot2' => [
                'attendenceId' => null,
                'inDate' => null,
                'inTime' => null,
                'outDate' => null,
                'outTime' => null,
                'inLate' => 0,
                'earlyOut' => 0,
                'slot2InDateTime' => null,
                'slot2OutDateTime' => null
            ]
        ];

        if (sizeof($relatedAttendanceRecords) == 2) {
            $slot1InDate = $relatedAttendanceRecords[0]->in ? date('Y-m-d', strtotime($relatedAttendanceRecords[0]->in)) : null;
            $slot1InTime = $relatedAttendanceRecords[0]->in ? date('h:i A', strtotime($relatedAttendanceRecords[0]->in)) : null;
            $slot1OutDate = $relatedAttendanceRecords[0]->out ? date('Y-m-d', strtotime($relatedAttendanceRecords[0]->out)) : null;
            $slot1OutTime = $relatedAttendanceRecords[0]->out ? date('h:i A', strtotime($relatedAttendanceRecords[0]->out)) : null;

            $slot1InDateTime = (!empty($slot1InDate) && !empty($slot1InTime)) ? $slot1InDate.' '.$slot1InTime : null;
            $slot1OutDateTime = (!empty($slot1OutDate) && !empty($slot1OutTime)) ? $slot1OutDate.' '.$slot1OutTime : null;


            $slot2InDate = $relatedAttendanceRecords[1]->in ? date('Y-m-d', strtotime($relatedAttendanceRecords[1]->in)) : null;
            $slot2InTime = $relatedAttendanceRecords[1]->in ? date('h:i A', strtotime($relatedAttendanceRecords[1]->in)) : null;
            $slot2OutDate = $relatedAttendanceRecords[1]->out ? date('Y-m-d', strtotime($relatedAttendanceRecords[1]->out)) : null;
            $slot2OutTime = $relatedAttendanceRecords[1]->out ? date('h:i A', strtotime($relatedAttendanceRecords[1]->out)) : null;

            $slot2InDateTime = (!empty($slot2InDate) && !empty($slot2InTime)) ? $slot2InDate.' '.$slot2InTime : null;
            $slot2OutDateTime = (!empty($slot2OutDate) && !empty($slot2OutTime)) ? $slot2OutDate.' '.$slot2OutTime : null;

            // calculate earlyOut
            $expectedOut = new DateTime($expectedOut, new DateTimeZone($timeZone));
            $slot1actualOut = (!empty($slot1OutDateTime)) ? new DateTime($slot1OutDateTime, new DateTimeZone($timeZone)) : null;
            $slot2actualOut = (!empty($slot2OutDateTime)) ? new DateTime($slot2OutDateTime, new DateTimeZone($timeZone)) : null;

            if (!empty($slot1actualOut)) {
                $slot1EarlyCountInSec = $this->timeDiffSeconds($slot1actualOut, $expectedOut, true);
                $slot1EarlyCountInMin = (int) ($slot1EarlyCountInSec / 60);
            } else {
                $slot1EarlyCountInMin = 0;
            }

            if (!empty($slot2actualOut)) {
                $slot2EarlyCountInSec = $this->timeDiffSeconds($slot2actualOut, $expectedOut, true);
                $slot2EarlyCountInMin = (int) ($slot2EarlyCountInSec / 60);
            } else {
                $slot2EarlyCountInMin = 0;
            }

            // calculate lateIn
            $expectedIn = new DateTime($expectedIn, new DateTimeZone($timeZone));
            $slot1actualIn = (!empty($slot1InDateTime)) ?  new DateTime($slot1InDateTime, new DateTimeZone($timeZone)) : null;
            $slot2actualIn = (!empty($slot2InDateTime)) ? new DateTime($slot2InDateTime, new DateTimeZone($timeZone)) : null;

            if (!empty($slot1actualIn)) {
                $slot1LateCountInSec = $this->timeDiffSeconds($slot1actualIn, $expectedIn, false);
                $slot1LateCountInMin = (int) ($slot1LateCountInSec / 60);
            } else {
                $slot1LateCountInMin = 0;
            }

            if (!empty($slot2actualIn)) {

                $slot2LateCountInSec = $this->timeDiffSeconds($slot2actualIn, $expectedIn, false);
                $slot2LateCountInMin = (int) ($slot2LateCountInSec / 60);
            } else {
                $slot2LateCountInMin = 0;
            }

            $attendanceSlotDetails["slot1"]['attendenceId'] = $relatedAttendanceRecords[0]->id;
            $attendanceSlotDetails["slot1"]['inDate'] = $slot1InDate;
            $attendanceSlotDetails["slot1"]['inTime'] = $slot1InTime;
            $attendanceSlotDetails["slot1"]['outDate'] = $slot1OutDate;
            $attendanceSlotDetails["slot1"]['outTime'] = $slot1OutTime;
            $attendanceSlotDetails["slot1"]['inLate'] = $slot1LateCountInMin;
            $attendanceSlotDetails["slot1"]['earlyOut'] = $slot1EarlyCountInMin;
            $attendanceSlotDetails["slot1"]['slot1InDateTime'] = $slot1InDateTime;
            $attendanceSlotDetails["slot1"]['slot1OutDateTime'] = $slot1OutDateTime;
            

            $attendanceSlotDetails["slot2"]['attendenceId'] = $relatedAttendanceRecords[1]->id;
            $attendanceSlotDetails["slot2"]['inDate'] = $slot2InDate;
            $attendanceSlotDetails["slot2"]['inTime'] = $slot2InTime;
            $attendanceSlotDetails["slot2"]['outDate'] = $slot2OutDate;
            $attendanceSlotDetails["slot2"]['outTime'] = $slot2OutTime;
            $attendanceSlotDetails["slot2"]['inLate'] = $slot2LateCountInMin;
            $attendanceSlotDetails["slot2"]['earlyOut'] = $slot2EarlyCountInMin;
            $attendanceSlotDetails["slot2"]['slot2InDateTime'] = $slot2InDateTime;
            $attendanceSlotDetails["slot2"]['slot2OutDateTime'] = $slot2OutDateTime;
        } elseif (sizeof($relatedAttendanceRecords) == 1) {

            $slot1InDate = $relatedAttendanceRecords[0]->in ? date('Y-m-d', strtotime($relatedAttendanceRecords[0]->in)) : null;
            $slot1InTime = $relatedAttendanceRecords[0]->in ? date('h:i A', strtotime($relatedAttendanceRecords[0]->in)) : null;
            $slot1OutDate = $relatedAttendanceRecords[0]->out ? date('Y-m-d', strtotime($relatedAttendanceRecords[0]->out)) : null;
            $slot1OutTime = $relatedAttendanceRecords[0]->out ? date('h:i A', strtotime($relatedAttendanceRecords[0]->out)) : null;


            $slot1InDateTime = (!empty($slot1InDate) && !empty($slot1InTime)) ? $slot1InDate.' '.$slot1InTime : null;
            $slot1OutDateTime = (!empty($slot1OutDate) && !empty($slot1OutTime)) ? $slot1OutDate.' '.$slot1OutTime : null;

            // calculate earlyOut
            $expectedOut = new DateTime($expectedOut, new DateTimeZone($timeZone));
            $slot1actualOut = (!empty($slot1OutDateTime)) ? new DateTime($slot1OutDateTime, new DateTimeZone($timeZone)) : null;

            if (!empty($slot1actualOut)) {
                $slot1EarlyCountInSec = $this->timeDiffSeconds($slot1actualOut, $expectedOut, true);
                $slot1EarlyCountInMin = (int) ($slot1EarlyCountInSec / 60);
            } else {
                $slot1EarlyCountInMin = 0;
            }

            // calculate lateIn
            $expectedIn = new DateTime($expectedIn, new DateTimeZone($timeZone));
            $slot1actualIn = (!empty($slot1InDateTime)) ?  new DateTime($slot1InDateTime, new DateTimeZone($timeZone)) : null;

            if (!empty($slot1actualIn)) {
                $slot1LateCountInSec = $this->timeDiffSeconds($slot1actualIn, $expectedIn, false);
                $slot1LateCountInMin = (int) ($slot1LateCountInSec / 60);
            } else {
                $slot1LateCountInMin = 0;
            }

            $attendanceSlotDetails["slot1"]['attendenceId'] = $relatedAttendanceRecords[0]->id;
            $attendanceSlotDetails["slot1"]['inDate'] = $slot1InDate;
            $attendanceSlotDetails["slot1"]['inTime'] = $slot1InTime;
            $attendanceSlotDetails["slot1"]['outDate'] = $slot1OutDate;
            $attendanceSlotDetails["slot1"]['outTime'] = $slot1OutTime;
            $attendanceSlotDetails["slot1"]['inLate'] = $slot1LateCountInMin;
            $attendanceSlotDetails["slot1"]['earlyOut'] = $slot1EarlyCountInMin;
            $attendanceSlotDetails["slot1"]['slot1InDateTime'] = $slot1InDateTime;
            $attendanceSlotDetails["slot1"]['slot1OutDateTime'] = $slot1OutDateTime;
        }

        return $attendanceSlotDetails;

    }

    public function getDailySummeryData($employeeId, $summaryDate)
    {
        try {
            $employeeId = is_null($employeeId) ?  $this->session->getEmployee()->id : $employeeId;

            if (!$employeeId) {
                return $this->error(404, Lang::get('attendanceMessages.basic.ERR_NOT_EXIST'));
            }

            $employee = DB::table('employee')->select(DB::raw("CONCAT(firstName,' ',lastName) AS employeeName"))->where('id', '=', $employeeId)->first();
            if (empty($employee)) {
                return $this->error(404, Lang::get('attendanceMessages.basic.ERR_NOT_EXIST'));
            }

            $employeeName = $employee->employeeName;

            $attendanceSummary = DB::table('attendance_summary')
                ->where('employeeId', $employeeId)->where('date', $summaryDate)
                ->first(['id', 'dayTypeId', 'shiftId', 'workTime', 'breakTime', 'actualIn', 'actualOut']);

            if (empty($attendanceSummary)) {
                throw new Exception("Record not exist"); 
            }

            $shift = DB::table('workShifts')
                ->join('workShiftDayType', 'workShiftDayType.workShiftId', '=', 'workShifts.id')
                ->where('workShifts.id', $attendanceSummary->shiftId)
                ->where('workShiftDayType.dayTypeId', $attendanceSummary->dayTypeId)
                ->first(['workShifts.name', 'workShiftDayType.startTime', 'workShiftDayType.endTime']);

            $query = "
            SELECT
                attendance.id as attendanceId,
                attendance.date as shiftDate,
                attendance.in as clockIn,
                attendance.out as clockOut,
                attendance.shiftId as shiftId,
                attendance.lateIn,
                attendance.earlyOut,
                attendance.workedHours as workedHours,
                break.id as breakId,
                break.in as breakIn,
                break.out as breakOut,
                break.diff as breakHours
            FROM attendance
                LEFT JOIN attendance_summary ON attendance_summary.id = attendance.summaryId
            	LEFT JOIN break ON break.attendanceId = attendance.id
                WHERE attendance_summary.id = '{$attendanceSummary->id}'
                GROUP BY attendance.id, break.id
            ORDER BY attendance.shiftId, attendance.id, break.id
            ;
            ";

            $dataSet = DB::select($query);

            $shiftRecords = [];
            $attendanceRecords = [];
            $breakRecords = [];

            $totalWorkedTime = 0;
            $totalBreakTime = 0;
            $shiftLate = 0;
            $firstIn = 0;
            $lastOut = 0;
            $lateIn = 0;
            $earlyDeparture = 0;

            $tempShift = '';
            $tempAttendance = '';
            $shiftWorkedTime = 0;
            $newShift = new stdClass();
            $newAttendance = new stdClass();

            foreach ($dataSet as $key => $row) {
                // check is it a new shift
                if ($row->shiftId !== $tempShift) {
                    if ($tempShift !== '') {
                        $shiftLate = $row->lateIn;
                        $newShift->workedTime = gmdate("H:i:s", $shiftWorkedTime * 60);
                        $newShift->lateTime = gmdate("H:i:s", $shiftLate * 60);
                        $newShift->attendanceRecords = $attendanceRecords;
                        array_push($shiftRecords, $newShift);
                    }

                    $tempShift = $row->shiftId;

                    $newShift = new stdClass();
                    $attendanceRecords = [];
                    $breakRecords = [];
                    $shiftWorkedTime = 0;
                    $shiftLate = 0;
                    $newShift->shiftName = isset($shift->name) ? $shift->name : '-';
                    $newShift->range = (isset($shift->startTime) && isset($shift->endTime)) ? "$shift->startTime - $shift->endTime" : "-";
                    $newShift->shiftStartTime = isset($shift->startTime) ? $shift->startTime : "";
                    $newShift->shiftEndTime = isset($shift->endTime) ? $shift->endTime : "";
                    $newShift->shiftDate = $row->shiftDate;
                }

                // check is it a new check in
                if ($row->attendanceId !== $tempAttendance) {
                    if ($tempAttendance !== '') {
                        $newAttendance->breakRecords = $breakRecords;
                        array_push($attendanceRecords, $newAttendance);
                    }

                    $tempAttendance = $row->attendanceId;

                    $newAttendance = new stdClass();
                    $breakRecords = [];
                    $newAttendance->clockId = $row->attendanceId;
                    $newAttendance->clockIn = $row->clockIn;
                    $newAttendance->clockOut = $row->clockOut;
                    $shiftWorkedTime = $shiftWorkedTime + (int)$row->workedHours;
                    $totalWorkedTime = $totalWorkedTime + (int)$row->workedHours;
                }

                // check is punched in have breaks
                if (!is_null($row->breakId)) {
                    $newBreak = new stdClass();
                    $newBreak->breakIn = $row->breakIn;
                    $newBreak->breakOut = $row->breakOut;
                    $newBreak->breakTime = gmdate("H:i:s", $row->breakHours * 60);
                    $totalBreakTime = $totalBreakTime + (int)$row->breakHours;
                    array_push($breakRecords, $newBreak);
                }

                // check is it last record
                if ((count($dataSet) - 1) === $key) {
                    $newAttendance->breakRecords = $breakRecords;
                    array_push($attendanceRecords, $newAttendance);

                    $shiftLate = $row->lateIn;
                    $newShift->workedTime = gmdate("H:i:s", $shiftWorkedTime * 60);
                    $newShift->lateTime = gmdate("H:i:s", $shiftLate * 60);
                    $newShift->attendanceRecords = $attendanceRecords;
                    array_push($shiftRecords, $newShift);
                }

                // set first in out as min in and max out
                if ($key === 0) {
                    $firstIn = strtotime($row->clockIn);
                    $lateIn = $row->lateIn;

                    $lastOut = strtotime($row->clockOut);
                    $earlyDeparture = $row->earlyOut;
                }

                // get old push in
                if ($firstIn > strtotime($row->clockIn)) {
                    $firstIn = strtotime($row->clockIn);
                    $lateIn = $row->lateIn;
                }

                // get latest punched out
                if ($lastOut < strtotime($row->clockOut)) {
                    $lastOut = strtotime($row->clockOut);
                    $earlyDeparture = $row->earlyOut;
                }
            }

            $response = new stdClass();
            $response->shiftRecords = $shiftRecords;
            $response->shiftsInTime = $attendanceSummary->actualIn ? date('Y-m-d H:i:s', strtotime($attendanceSummary->actualIn)) : null;
            $response->shiftsOutTime = $attendanceSummary->actualOut ? date('Y-m-d H:i:s', strtotime($attendanceSummary->actualOut)) : null;
            $response->totalWorkedTime = gmdate("H:i:s", $attendanceSummary->workTime * 60);
            $response->totalBreakTime = gmdate("H:i:s", $attendanceSummary->breakTime * 60);
            $response->lateIn = '-';
            $response->earlyDeparture = '-';
            $response->summaryDate = $summaryDate;
            $response->employeeName = $employeeName;

            if (is_null($attendanceSummary->shiftId)) {
                return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $response);
            }

            // TODO need to add leaves
            $response->shiftRecords = $shiftRecords;
            $response->shiftsInTime = $firstIn ? date('Y-m-d H:i:s', $firstIn) : null;
            $response->shiftsOutTime = $lastOut ? date('Y-m-d H:i:s', $lastOut) : null;
            $response->totalWorkedTime = gmdate("H:i:s", $totalWorkedTime * 60);
            $response->totalBreakTime = gmdate("H:i:s", $totalBreakTime * 60);
            $response->lateIn = gmdate("H:i:s", $lateIn * 60);
            $response->earlyDeparture = gmdate("H:i:s", $earlyDeparture * 60);
            $response->summaryDate = $summaryDate;
            $response->employeeName = $employeeName;

            return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $response);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function updateAttendanceTime($data)
    {
        try {
            DB::beginTransaction();
            $employeeId = $this->session->getEmployee()->id;
            $isGoThroughWf = true;

            $dataObject = (object) $data;
            $shiftId = $dataObject->shiftId;
            $summaryId = $dataObject->summaryId;
            $shiftDate = $dataObject->shiftDate;
            $inDate = $dataObject->inDate;
            $outDate = $dataObject->outDate;
            $inTime = $dataObject->inTime;
            $outTime = $dataObject->outTime;
            $reason = $dataObject->reason;
            $breakDetails = $dataObject->breakDetails;
            unset($dataObject->breakDetails);


            if (!$summaryId || !$shiftDate || !$inDate || !$outDate || !$inTime || !$outTime) {
                throw new Exception("Missed at least one property");
            }

            $inDateTime = strtotime($inDate . ' ' . $inTime);
            $outDateTime = strtotime($outDate . ' ' . $outTime);

            if ($inDateTime > $outDateTime) {
                throw new Exception("Out date time should be greater than in date time");
            }

            $invalidDateCountInBreaks = 0;
            $inDateTimeObj = Carbon::createFromFormat('Y-m-d H:i:s', $inDate . ' ' . $inTime);
            $outDateTimeObj = Carbon::createFromFormat('Y-m-d H:i:s', $outDate . ' ' . $outTime);

            $dates = [];

            if (!is_null($inDate) && !in_array($inDate, $dates)) {
                $dates[] = $inDate;
            }

            if (!is_null($outDate) && !in_array($outDate, $dates)) {
                $dates[] = $outDate;
            }


            //check this attendance record is already approved one for ot
            $postOtRequestRecord =  DB::table('postOtRequestDetail')
                ->where('summaryId', '=', $summaryId)
                ->whereIn('status', ['PENDING', 'APPROVED'])
                ->first();

            if (!is_null($postOtRequestRecord)) {
                DB::rollback();
                return $this->error(500, Lang::get('attendanceMessages.basic.ERR_ALREADY_USE_FOR_OT_APPROVE_PROCESS'), null);
            }

            //check whether self service reuest type is locked
            $hasLockedSelfService = $this->checkSelfServiceRecordLockIsEnable($dates, 'timeChangeRequest');
            
            if ($hasLockedSelfService) {
                DB::rollback();
                return $this->error(500, Lang::get('attendanceMessages.basic.ERR_HAS_LOCKED_SELF_SERVICE'), null);
            }

            //check whether requested date related attendane summary records are locked
            $hasLockedRecords = $this->checAttendanceRecordIsLocked($employeeId, $dates);

            if ($hasLockedRecords) {
                DB::rollback();
                return $this->error(500, Lang::get('attendanceMessages.basic.ERR_CANNOT_APPLY_TIME_CHANGE_DUE_TO_LOCKED_ATTENDANCE_RECORDS'), null);
            }

            $newTimeChangeRequest = new stdClass();
            $newTimeChangeRequest->shiftId = $shiftId;
            $newTimeChangeRequest->shiftDate = $shiftDate;
            $newTimeChangeRequest->employeeId = $employeeId;
            $newTimeChangeRequest->summaryId = $summaryId;
            $newTimeChangeRequest->inDateTime = date('Y-m-d H:i:s', $inDateTime);
            $newTimeChangeRequest->outDateTime = date('Y-m-d H:i:s', $outDateTime);
            $newTimeChangeRequest->reason = $reason;
            $newTimeChangeRequest->type = 0;

            $newTimeChangeRequestArray = (array) $newTimeChangeRequest;
            $savedTimeChangeRequest = $this->store->insert($this->attendance_timeChange_model, $newTimeChangeRequestArray, true);

            if (!empty($breakDetails)) {
                foreach ($breakDetails as $breakKey => $breakRecord) {
                    $breakRecord = (array)  $breakRecord;

                    $breakRecord = [
                        'timeChangeRequestId' =>  $savedTimeChangeRequest['id'],
                        'breakInDateTime' =>  $breakRecord['breakInDate'] . ' ' . $breakRecord['breakInTime'],
                        'breakOutDateTime' =>  $breakRecord['breakOutDate'] . ' ' . $breakRecord['breakOutTime']
                    ];

                    $breakInDateObj = Carbon::createFromFormat('Y-m-d H:i:s', $breakRecord['breakInDateTime']);
                    $breakOutDateObj = Carbon::createFromFormat('Y-m-d H:i:s', $breakRecord['breakOutDateTime']);

                    if (!($breakInDateObj->between($inDateTimeObj, $outDateTimeObj) && $breakOutDateObj->between($inDateTimeObj, $outDateTimeObj))) {
                        DB::rollback();
                        return $this->error(502, 'break time ranges must be within the actual in and actual out time range', null);
                    }

                    $timeChangeBreakRecordId = DB::table('timeChangeRequestBreakDetails')
                        ->insertGetId($breakRecord);
                }
            }

            //create workflow instance for time change request
            if ($isGoThroughWf) {
                $timeChangeRequestDataSet = (array) $this->store->getById($this->attendance_timeChange_model, $savedTimeChangeRequest['id']);
                if (is_null($timeChangeRequestDataSet)) {
                    return $this->error(404, 'Time change request not exsist', $savedTimeChangeRequest['id']);
                }

                $timeChangeBreakRecords = DB::table('timeChangeRequestBreakDetails')->where('timeChangeRequestId', $savedTimeChangeRequest['id'])->get();

                $timeChangeRequestDataSet['breakDetails'] = (!empty($timeChangeBreakRecords)) ? $timeChangeBreakRecords : [];

                // this is the workflow context id related for Time  Change Request
                $context = 3;

                $selectedWorkflow = $this->workflowService->filterRelatedWorkflow($context, $employeeId);
                if (isset($selectedWorkflow['error']) && $selectedWorkflow['error']) {
                    DB::rollback();
                    return $this->error($selectedWorkflow['statusCode'], $selectedWorkflow['message'], null);
                }

                $workflowDefineId = $selectedWorkflow;

                //send this time change request through workflow process
                $workflowInstanceRes = $this->workflowService->runWorkflowProcess($workflowDefineId, $timeChangeRequestDataSet, $employeeId);
                if ($workflowInstanceRes['error']) {
                    DB::rollback();
                    return $this->error($workflowInstanceRes['statusCode'], $workflowInstanceRes['message'], $workflowDefineId);
                }
                unset($timeChangeRequestDataSet['breakDetails']);
                $timeChangeRequestDataSet['workflowInstanceId'] = $workflowInstanceRes['data']['instanceId'];
                $updateTimeChangeRequest = $this->store->updateById($this->attendance_timeChange_model, $savedTimeChangeRequest['id'], $timeChangeRequestDataSet);

                if (!$updateTimeChangeRequest) {
                    DB::rollback();
                    return $this->error(502, Lang::get('attendanceMessages.basic.ERR_UPDATE'), $savedTimeChangeRequest['id']);
                }

                DB::commit();
                return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GO_WF'), $savedTimeChangeRequest);
            }
            DB::commit();
            return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $savedTimeChangeRequest);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function requestAttendanceTime($request)
    {
        try {
            // TODO check this user is an manager for this employee
            // $employeeId = $this->session->getEmployee()->id;

            $timeChangeId = $request->query('requestedTimeChangeId', null);

            $timeChangeRequest = $this->store->getFacade()::table('time_change_requests')->where('id', $timeChangeId)->first();

            //get related breaks
            $timeChangeRequestBreaks = $this->store->getFacade()::table('timeChangeRequestBreakDetails')->where('timeChangeRequestId', $timeChangeId)->get();
            $timeChangeRequest->workflowId = null;
            $timeChangeRequest->breakDetails = (!empty($timeChangeRequestBreaks)) ? $timeChangeRequestBreaks : [];
            if (!empty($timeChangeRequest->workflowInstanceId)) {
                $wfInstanceData = (array) $this->store->getById($this->workflowInstanceModel, $timeChangeRequest->workflowInstanceId);
                $timeChangeRequest->workflowId = $wfInstanceData['workflowId'];
                $timeChangeRequest->actionIds = $wfInstanceData['actionId'];
                $timeChangeRequest->contextId = $wfInstanceData['contextId'];
            } else {
                $timeChangeRequest->workflowId = null;
                $timeChangeRequest->actionIds = null;
                $timeChangeRequest->contextId = null;
            }

            return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $timeChangeRequest);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function approveAttendanceTime($data)
    {
        try {
            // TODO check this user is an manager for this employee
            $approvedId = $this->session->getEmployee()->id;

            $dataObject = (object) $data;
            $timeChangeId = $dataObject->timeChangeId;
            $type = $dataObject->type;
            $shiftId = $dataObject->shiftId;
            $employeeId = $dataObject->employeeId;
            $summaryId = $dataObject->summaryId;

            $timeChangeRequest = $this->store->getFacade()::table('time_change_requests')->where('id', $timeChangeId)->first();

            if (!$timeChangeRequest) {
                return $this->error(404, Lang::get('attendanceMessages.basic.ERR_NOT_EXIST'));
            }

            $updateTime = new stdClass();
            $updateTime = $timeChangeRequest;
            $updateTime->approvedId =  $approvedId;
            $updateTime->type = $type;
            $updatedRequest = (object) $this->store->updateById($this->attendance_timeChange_model, $timeChangeId, (array) $updateTime, true);
            $savedNewAttendance = $updatedRequest;
            if ($type == 2) {
                $savedNewAttendance = $this->timeChangeProcess($employeeId, $timeChangeRequest, $summaryId);

                if (!empty($savedNewAttendance['id'])) {
                    //check whether time change request has related breaks
                    $relatedBreaks = $this->store->getFacade()::table('timeChangeRequestBreakDetails')->where('timeChangeRequestId', $timeChangeId)->get();

                    if (!empty($relatedBreaks)) {
                        foreach ($relatedBreaks as $key => $break) {
                            $break = (array) $break;
                            $breakInObj = Carbon::parse($break['breakInDateTime']);
                            $breakOutObj = Carbon::parse($break['breakOutDateTime']);

                            $diffInMin =  $breakInObj->diffInMinutes($breakOutObj, false);
                            $breakRecord = [
                                'attendanceId' => $savedNewAttendance['id'],
                                'in' => $break['breakInDateTime'],
                                'out' => $break['breakOutDateTime'],
                                'diff' => $diffInMin
                            ];
                            $savedNewBreak = $this->store->insert($this->breakModel, $breakRecord, true);
                            if (!$savedNewBreak) {
                                return $this->error(502, Lang::get('attendanceMessages.basic.ERR_CREATE'), $savedNewBreak);
                            }
                        }
                    }
                }
            }

            return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $savedNewAttendance);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    private function timeChangeProcess($employeeId, $updatedRequest, $summaryId)
    {
        try {
            // get attendance and update
            $summarySaved = $this->store->getFacade()::table('attendance_summary')->where('employeeId', $employeeId)->where('id', $summaryId)->first();

            $timeZone = $summarySaved->timeZone;

            $shiftData = null;
            if (!is_null($summarySaved->shiftId)) {
                $shiftData = $this->store->getFacade()::table('workShifts')
                    ->leftJoin('workShiftDayType', 'workShiftDayType.workShiftId', '=', 'workShifts.id')
                    ->where('workShiftDayType.dayTypeId', $summarySaved->dayTypeId)
                    ->where('workShifts.id', $summarySaved->shiftId)->first();
            }

            if (is_null($timeZone)) {
                $dateObj = Carbon::now('UTC');
                $company = DB::table('company')->first('timeZone');
                $companyDate = $dateObj->tz($company->timeZone);
                $employeeJob = $this->getEmployeeJob($employeeId, $companyDate->format('Y-m-d'), ['calendarId', 'locationId']);;

                if (!$employeeId || !$employeeJob) {
                    return $this->error(404, Lang::get('attendanceMessages.basic.ERR_NOT_EXIST'));
                }

                $location = $this->store->getFacade()::table('location')->where('id', $employeeJob->locationId)->first();
                if (!$location) {
                    return $this->error(404, Lang::get('attendanceMessages.basic.ERR_NOT_EXIST'));
                }

                $timeZone = $location->timeZone;
            }

            $inTime = $updatedRequest->inDateTime;
            $outTime = $updatedRequest->outDateTime;
            $inTimeDate = new DateTime($inTime, new DateTimeZone($timeZone));
            $outTimeDate = new DateTime($outTime, new DateTimeZone($timeZone));
            $inTimeDate->setTimezone(new DateTimeZone("UTC"));
            $outTimeDate->setTimezone(new DateTimeZone("UTC"));
            $inTimeUTC = $inTimeDate->format("Y-m-d H:i:s e");
            $outTimeUTC = $outTimeDate->format("Y-m-d H:i:s e");

            // get attendance for that employee shift
            $attendancesSaved = $this->store->getFacade()::table('attendance')->where('employeeId', $employeeId)->where('summaryId', $summaryId)->orderBy("in", "asc")->get();

            // move related data to history
            foreach ($attendancesSaved as $key => $attendance) {
                // send attendance to history
                $newAttendanceHistory = new stdClass();
                $newAttendanceHistory->date = $attendance->date;
                $newAttendanceHistory->timeZone = $attendance->timeZone;
                $newAttendanceHistory->in = $attendance->in;
                $newAttendanceHistory->out = $attendance->out;
                $newAttendanceHistory->inUTC = $attendance->inUTC;
                $newAttendanceHistory->outUTC = $attendance->outUTC;
                $newAttendanceHistory->typeId = $attendance->typeId;
                $newAttendanceHistory->attendanceId = $attendance->id;
                $newAttendanceHistory->earlyOut = $attendance->earlyOut;
                $newAttendanceHistory->lateIn = $attendance->lateIn;
                $newAttendanceHistory->workedHours = $attendance->workedHours;
                $newAttendanceHistory->breakHours = $attendance->breakHours;

                $newAttendanceHistoryArray = (array) $newAttendanceHistory;
                $savedAttendanceHistory = (object) $this->store->insert($this->attendance_history_model, $newAttendanceHistoryArray, true);

                // get related breaks
                $breaksSaved = $this->store->getFacade()::table('break')->where('attendanceId', $attendance->id)->get();

                foreach ($breaksSaved as $keyBreak => $break) {
                    // send break to history
                    $newBreakHistory = new stdClass();
                    $newBreakHistory->attendanceHistoryId = $savedAttendanceHistory->id;
                    $newBreakHistory->in = $break->in;
                    $newBreakHistory->out = $break->out;
                    $newBreakHistory->diff = $break->diff;

                    $newBreakHistoryArray = (array) $newBreakHistory;
                    $savedBreakHistory = $this->store->insert($this->break_history_model, $newBreakHistoryArray, true);

                    // delete break
                    $this->store->deleteById($this->breakModel, $break->id);
                }

                // delete attendance
                $this->store->deleteById($this->attendanceModel, $attendance->id);
            }

            // calculate worked time
            $differenceInSec = $this->timeDiffSeconds($outTimeDate, $inTimeDate, false);
            $differenceInMin = (int) ($differenceInSec / 60);

            // calculate lateIn
            $expectedIn = (!is_null($summarySaved->expectedIn)) ?  new DateTime($summarySaved->expectedIn) : null;
            $actualIn = new DateTime($inTime);
            $lateCountInSec = (!is_null($actualIn) && !is_null($expectedIn)) ?  $this->timeDiffSeconds($actualIn, $expectedIn, false) : 0;
            $lateCountInMin = $lateCountInSec == 0 ? 0 : (int) ($lateCountInSec / 60);
            $gracePeriod = (!is_null($shiftData) && !is_null($shiftData->gracePeriod)) ? (int) $shiftData->gracePeriod : 0;

            // calculate earlyOut
            $expectedOut = new DateTime($summarySaved->expectedOut);
            $actualOut = new DateTime($outTime);
            $earlyCountInSec = $this->timeDiffSeconds($actualOut, $expectedOut, true);
            $earlyCountInMin = (int) ($earlyCountInSec / 60);

            // update summary
            $updateSummary = new stdClass();
            $updateSummary = $summarySaved;
            $updateSummary->actualIn = $inTime;
            $updateSummary->actualInUTC = $inTimeUTC;
            $updateSummary->actualOut = $outTime;
            $updateSummary->actualOutUTC = $outTimeUTC;
            $updateSummary->isLateIn = $lateCountInMin > 0 && $lateCountInMin > $gracePeriod;
            $updateSummary->lateIn = $lateCountInMin > 0 && $lateCountInMin > $gracePeriod ? $lateCountInMin : 0;
            $updateSummary->isEarlyOut = $earlyCountInMin > 0;
            $updateSummary->earlyOut = $earlyCountInMin;
            $updateSummary->workTime = $differenceInMin;
            $updateSummary->breakTime = 0;
            $updateSummary->isPresent = true;
            $updateSummary->isNoPay = false;
            $updateSummary = (array) $updateSummary;

            $updatedAttendance = $this->store->updateById($this->attendance_summary_model, $summarySaved->id, $updateSummary, true);

            // insert new attendance
            $newAttendance = new stdClass();
            $newAttendance->date = $summarySaved->date;
            $newAttendance->in = $inTime;
            $newAttendance->inUTC = $inTimeUTC;
            $newAttendance->out = $outTime;
            $newAttendance->outUTC = $outTimeUTC;
            $newAttendance->typeId = 0;
            $newAttendance->employeeId = $employeeId;
            $newAttendance->calendarId = 0;
            $newAttendance->shiftId = $summarySaved->shiftId;
            $newAttendance->summaryId = $summarySaved->id;
            $newAttendance->timeZone = $timeZone;
            $newAttendance->earlyOut = $earlyCountInMin;
            $newAttendance->lateIn =  $lateCountInMin > 0 && $lateCountInMin > $gracePeriod ? $lateCountInMin : 0;
            $newAttendance->workedHours = $differenceInMin;
            $newAttendance->breakHours = 0;

            $newAttendanceArray = (array) $newAttendance;
            $savedNewAttendance = $this->store->insert($this->attendanceModel, $newAttendanceArray, true);

            return $savedNewAttendance;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function updateAttendanceTimeAdmin($data)
    {
        try {
            $approvedId = null;

            $dataObject = (object) $data;
            $shiftId = $dataObject->shiftId;
            $employeeId =  $dataObject->employeeId;
            $shiftDate = $dataObject->shiftDate;
            $inDate = $dataObject->inDate;
            $outDate = $dataObject->outDate;
            $inTime = $dataObject->inTime;
            $outTime = $dataObject->outTime;
            $reason = $dataObject->reason;
            $summaryId = $dataObject->summaryId;
            $breakDetails = $dataObject->breakDetails;
            unset($dataObject->breakDetails);

            if (!$summaryId || !$employeeId || !$shiftDate || !$inDate || !$outDate || !$inTime || !$outTime) {
                throw new Exception("Missed at least one property");
            }

            //get related summery record
            $originalSummeryRecord = DB::table('attendance_summary')->where('id', $summaryId)->first();

            //if shift id is changed need to create adhoc shift for this perticular date for this employee
            if ($shiftId != $originalSummeryRecord->shiftId) {
                $checkShiftExists = DB::table('adhocWorkshifts')->where('date',$shiftDate)->where('employeeId',$employeeId)->first();
                $workShiftId = $shiftId;
                if (empty($checkShiftExists)) {
                    $adhocWorkShift = DB::table('adhocWorkshifts')
                       ->insertGetId([
                          'workshiftId' => $workShiftId, 
                          'date'       => $shiftDate,
                          'employeeId' => $employeeId
                        ]);
                } else {
                    $adhocWorkShift = DB::table('adhocWorkshifts')->where('id',$checkShiftExists->id)->update(['workShiftId' =>   $workShiftId]);
                }

            }


            $inDateTime = strtotime($inDate . ' ' . $inTime);
            $outDateTime = strtotime($outDate . ' ' . $outTime);

            $inDateTimeObj = Carbon::createFromFormat('Y-m-d H:i:s', $inDate . ' ' . $inTime);
            $outDateTimeObj = Carbon::createFromFormat('Y-m-d H:i:s', $outDate . ' ' . $outTime);

            if ($inDateTime > $outDateTime) {
                throw new Exception("Out date time should be greater than in date time");
            }

            $newTimeChangeRequest = new stdClass();
            $newTimeChangeRequest->shiftId = $shiftId;
            $newTimeChangeRequest->shiftDate = $shiftDate;
            $newTimeChangeRequest->employeeId = $employeeId;
            $newTimeChangeRequest->inDateTime = date('Y-m-d H:i:s', $inDateTime);
            $newTimeChangeRequest->outDateTime = date('Y-m-d H:i:s', $outDateTime);
            $newTimeChangeRequest->reason = $reason;
            $newTimeChangeRequest->type = 3;
            $newTimeChangeRequest->approvedId =  $approvedId;

            $dates = [];

            if (!is_null($inDate) && !in_array($inDate, $dates)) {
                $dates[] = $inDate;
            }

            if (!is_null($outDate) && !in_array($outDate, $dates)) {
                $dates[] = $outDate;
            }


            $newTimeChangeRequestArray = (array) $newTimeChangeRequest;
            $savedTimeChangeRequest = $this->store->insert($this->attendance_timeChange_model, $newTimeChangeRequestArray, true);

            if (!empty($breakDetails)) {
                foreach ($breakDetails as $breakKey => $breakRecord) {
                    $breakRecord = (array)  $breakRecord;
                    $breakRecord = [
                        'timeChangeRequestId' =>  $savedTimeChangeRequest['id'],
                        'breakInDateTime' =>  $breakRecord['breakInDate'] . ' ' . $breakRecord['breakInTime'],
                        'breakOutDateTime' =>  $breakRecord['breakOutDate'] . ' ' . $breakRecord['breakOutTime']
                    ];

                    $breakInDateObj = Carbon::createFromFormat('Y-m-d H:i:s', $breakRecord['breakInDateTime']);
                    $breakOutDateObj = Carbon::createFromFormat('Y-m-d H:i:s', $breakRecord['breakOutDateTime']);

                    if (!($breakInDateObj->between($inDateTimeObj, $outDateTimeObj) && $breakOutDateObj->between($inDateTimeObj, $outDateTimeObj))) {
                        return $this->error(502, 'break time ranges must be within the actual in and actual out time range', null);
                    }


                    $timeChangeBreakRecordId = DB::table('timeChangeRequestBreakDetails')
                        ->insertGetId($breakRecord);
                }
            }

            $savedNewAttendance = $this->timeChangeProcess($employeeId, $newTimeChangeRequest, $summaryId);

            if (!empty($savedNewAttendance['id'])) {
                //check whether time change request has related breaks
                $relatedBreaks = $this->store->getFacade()::table('timeChangeRequestBreakDetails')->where('timeChangeRequestId', $savedTimeChangeRequest['id'])->get();

                if (!empty($relatedBreaks)) {
                    foreach ($relatedBreaks as $key => $break) {
                        $break = (array) $break;
                        $breakInObj = Carbon::parse($break['breakInDateTime']);
                        $breakOutObj = Carbon::parse($break['breakOutDateTime']);

                        $diffInMin =  $breakInObj->diffInMinutes($breakOutObj, false);
                        $breakRecord = [
                            'attendanceId' => $savedNewAttendance['id'],
                            'in' => $break['breakInDateTime'],
                            'out' => $break['breakOutDateTime'],
                            'diff' => $diffInMin
                        ];
                        $savedNewBreak = $this->store->insert($this->breakModel, $breakRecord, true);
                        if (!$savedNewBreak) {
                            return $this->error(502, Lang::get('attendanceMessages.basic.ERR_CREATE'), $savedNewBreak);
                        }
                    }
                }
            }

            $dataSet = [
                'employeeId' => $employeeId,
                'dates' => $dates
            ];

            event(new AttendanceDateDetailChangedEvent($dataSet));
            $attendanceRecord = $this->getAttendanceDateData($employeeId, $shiftDate);

            return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $attendanceRecord);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    /**
     * Following function update invalid attendance data
     *
     * @param $data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Invalid Attendance Update Sucessfully",     *
     */
    public function updateInvalidAttendances($data)
    {
        try {
            DB::beginTransaction();

            $dataSet = (array) $data;
            $attendanceDetails = json_decode($dataSet['attendanceDetails']);

            foreach ($attendanceDetails as $summeryKey => $summeryRecord) {
                $summeryRecord = (array) $summeryRecord;
                $employeeId = $summeryRecord['employeeId'];
                $summaryId = $summeryRecord['summaryId'];

                //saved summary
                $summarySaved = $this->store->getFacade()::table('attendance_summary')->where('id', $summaryId)->first();

                $shiftData = null;
                if (!is_null($summarySaved->shiftId)) {
                    $shiftData = $this->store->getFacade()::table('workShifts')
                        ->leftJoin('workShiftDayType', 'workShiftDayType.workShiftId', '=', 'workShifts.id')
                        ->where('workShiftDayType.dayTypeId', $summarySaved->dayTypeId)
                        ->where('workShifts.id', $summarySaved->shiftId)->first();
                }

                // get attendance for that employee shift
                $attendancesSaved = $this->store->getFacade()::table('attendance')->where('employeeId', $employeeId)->where('summaryId', $summaryId)->orderBy("in", "asc")->get();

                // move related data to history
                foreach ($attendancesSaved as $key => $attendance) {
                    // send attendance to history
                    $newAttendanceHistory = new stdClass();
                    $newAttendanceHistory->date = $attendance->date;
                    $newAttendanceHistory->timeZone = $attendance->timeZone;
                    $newAttendanceHistory->in = $attendance->in;
                    $newAttendanceHistory->out = $attendance->out;
                    $newAttendanceHistory->inUTC = $attendance->inUTC;
                    $newAttendanceHistory->outUTC = $attendance->outUTC;
                    $newAttendanceHistory->typeId = $attendance->typeId;
                    $newAttendanceHistory->attendanceId = $attendance->id;
                    $newAttendanceHistory->earlyOut = $attendance->earlyOut;
                    $newAttendanceHistory->lateIn = $attendance->lateIn;
                    $newAttendanceHistory->workedHours = $attendance->workedHours;
                    $newAttendanceHistory->breakHours = $attendance->breakHours;

                    $newAttendanceHistoryArray = (array) $newAttendanceHistory;
                    $savedAttendanceHistory = (object) $this->store->insert($this->attendance_history_model, $newAttendanceHistoryArray, true);

                    // get related breaks
                    $breaksSaved = $this->store->getFacade()::table('break')->where('attendanceId', $attendance->id)->get();

                    foreach ($breaksSaved as $keyBreak => $break) {
                        // send break to history
                        $newBreakHistory = new stdClass();
                        $newBreakHistory->attendanceHistoryId = $savedAttendanceHistory->id;
                        $newBreakHistory->in = $break->in;
                        $newBreakHistory->out = $break->out;
                        $newBreakHistory->diff = $break->diff;

                        $newBreakHistoryArray = (array) $newBreakHistory;
                        $savedBreakHistory = $this->store->insert($this->break_history_model, $newBreakHistoryArray, true);

                        // delete break
                        $this->store->deleteById($this->breakModel, $break->id);
                    }

                    // delete attendance
                    $this->store->deleteById($this->attendanceModel, $attendance->id);
                }

                $summeryRecord['slot1'] = (array) $summeryRecord['slot1'];
                $summeryRecord['slot2'] = (array) $summeryRecord['slot2'];
                $summaryData = [];

                if (!empty($summeryRecord['slot1']['inDate']) && !empty($summeryRecord['slot1']['inTime']) && !empty($summeryRecord['slot1']['outDate']) && !empty($summeryRecord['slot1']['outTime'])) {
                    $slot1Summary =  $this->createAttendanceRecord($summeryRecord, $summeryRecord['slot1'], $employeeId, $shiftData);
                    $summaryData  = $slot1Summary;
                }

                if (!empty($summeryRecord['slot2']['inDate']) && !empty($summeryRecord['slot2']['inTime']) && !empty($summeryRecord['slot2']['outDate']) && !empty($summeryRecord['slot2']['outTime'])) {
                    $slot2Summary = $this->createAttendanceRecord($summeryRecord, $summeryRecord['slot2'], $employeeId, $shiftData);

                    $summaryData->lastOut = $slot2Summary->lastOut;
                    $summaryData->lastOutUTC = $slot2Summary->lastOutUTC;
                    $summaryData->isEarlyOut = $slot2Summary->isEarlyOut;
                    $summaryData->earlyOut = $slot2Summary->earlyOut;
                    $summaryData->workTime = $summaryData->workTime + $slot2Summary->workTime;
                }

                $shiftWiseWorkTime = $this->calculateWorkTimeAccordingToShiftPeriod($summaryId, $employeeId, $summeryRecord['date'], $summeryRecord['expectedIn'], $summeryRecord['expectedOut']);

                // update summary
                $updateSummary = new stdClass();
                $updateSummary = $summarySaved;
                $updateSummary->actualIn = $summaryData->firstIn;
                $updateSummary->actualInUTC = $summaryData->firstInUTC;
                $updateSummary->isLateIn = $summaryData->lateIn > 0;
                $updateSummary->lateIn = $summaryData->lateIn;
                $updateSummary->isPresent = true;
                $updateSummary->isNoPay = false;
                $updateSummary->actualOut = $summaryData->lastOut;
                $updateSummary->actualOutUTC = $summaryData->lastOutUTC;
                $updateSummary->isEarlyOut = $summaryData->isEarlyOut;
                $updateSummary->earlyOut = $summaryData->earlyOut;
                $updateSummary->workTime = $summarySaved->workTime + $summaryData->workTime;
                $updateSummary->preShiftWorkTime = $shiftWiseWorkTime['preShiftWorkTime'];
                $updateSummary->withinShiftWorkTime = $shiftWiseWorkTime['withinShiftWorkTime'];
                $updateSummary->postShiftWorkTime = $shiftWiseWorkTime['postShiftWorkTime'];
                $updateSummaryArray = (array) $updateSummary;

                $summarySaved = $this->store->updateById($this->attendance_summary_model, $summarySaved->id, $updateSummaryArray, true);
                $summaryPayTypeDetailsSaved =  $this->calculateAttendanceRelatedOTDetails($summaryId, $updateSummaryArray);

                $dataSet = [
                    'employeeId' => $employeeId,
                    'dates' => [$summeryRecord['date']]
                ];

                event(new AttendanceDateDetailChangedEvent($dataSet));
            }
            DB::commit();

            return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $attendanceDetails);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }


    /**
     * Following function create post ot request
     *
     * @param $data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Post OT Request Create Sucessfully",     *
     */
    public function createPostOtRequest($data)
    {
        try {
            DB::beginTransaction();
            $isAllowWf = true;
            $employeeId = $this->session->employee->id;
            $openPostOtRecords = json_decode($data['openPostOtRecords']);

            $postOtRequestData = [
                'employeeId' => $employeeId,
                'month' => $data['month'],
                'totalRequestedOtMins' => $data['totalRequestedOtMins'],
                'workflowInstanceId' => null,
                'currentState' => null,
            ];
            
            $validationResponse = ModelValidator::validate($this->postOtRequestModel, $postOtRequestData, false);
            if (!empty($validationResponse)) {
                DB::rollback();
                return $this->error(400, Lang::get('attendanceMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $newPostOtRequest = $this->store->insert($this->postOtRequestModel, $postOtRequestData, true);
            $requestDates = [];
            if (sizeof($openPostOtRecords) > 0) {
                foreach ($openPostOtRecords as $key => $attendance) {
                    $attendance = (array) $attendance;
                    $requestDates[] = $attendance['date'];

                    $postOtRequestDetailData = [
                        'postOtRequestId' => $newPostOtRequest['id'],
                        'summaryId' => $attendance['summaryId'],
                        'shiftId' => $attendance['shiftId'],
                        'actualIn' => $attendance['actualIn'],
                        'actualOut' => $attendance['actualOut'],
                        'workTime' => $attendance['workTime'],
                        'totalActualOt' => $attendance['totalActualOtMins'],
                        'totalRequestedOt' => $attendance['totalRequestedOtMins'],
                        'totalApprovedOt' => $attendance['totalApprovedOtMins'],
                        'otDetails' => json_encode($attendance['otDataSet']),
                        'status' => 'PENDING',
                        'requestedEmployeeComment' => $attendance['requestedEmployeeComment'],
                    ];

                    $newPostOtRequestDetail = $this->store->insert($this->postOtRequestDetailModel, $postOtRequestDetailData, true);
                }
            }

            $requestType = 'postOtRequest';

            //check whether self service reuest type is lock locked
            $hasLockedSelfService = $this->checkSelfServiceRecordLockIsEnable($requestDates, $requestType);
            
            if ($hasLockedSelfService) {
                DB::rollback();
                return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_HAS_LOCKED_SELF_SERVICE'), null);
            }

            //check whether requested dates related attendane summary records are locked
            $hasLockedRecords = $this->checAttendanceRecordIsLocked($employeeId, $requestDates);
            
            if ($hasLockedRecords) {
                DB::rollback();
                return $this->error(500, Lang::get('leaveRequestMessages.basic.ERR_HAS_LOCKED_ATTENDANCE_RECORDS'), null);
            }

            if ($isAllowWf) {
                $createdPostOtRequest = (array) $this->store->getById($this->postOtRequestModel, $newPostOtRequest['id']);
                if (is_null($createdPostOtRequest)) {
                    return $this->error(404, Lang::get('claimRequestMessages.basic.ERR_CREATE'), $id);
                }
                
                // this is the workflow context id related for Apply Leave
                $context = 10;

                $selectedWorkflow = $this->workflowService->filterRelatedWorkflow($context, $employeeId);
                if (isset($selectedWorkflow['error']) && $selectedWorkflow['error']) {
                    DB::rollback();
                    return $this->error($selectedWorkflow['statusCode'], $selectedWorkflow['message'], null);
                }
                
                $workflowDefineId = $selectedWorkflow;
                //send this leave request through workflow process
                $workflowInstanceRes = $this->workflowService->runWorkflowProcess($workflowDefineId, $createdPostOtRequest, $employeeId);
                if ($workflowInstanceRes['error']) {
                    DB::rollback();
                    return $this->error($workflowInstanceRes['statusCode'], $workflowInstanceRes['message'], $workflowDefineId);
                }
               
                $postOtRequstUpdated['workflowInstanceId'] = $workflowInstanceRes['data']['instanceId'];
                $updatePostOtRequest = $this->store->updateById($this->postOtRequestModel, $newPostOtRequest['id'], $postOtRequstUpdated);
                if (!$updatePostOtRequest) {
                    DB::rollback();
                    return $this->error(500, Lang::get('claimRequestMessages.basic.ERR_CREATE'),$newPostOtRequest['id']);
                }                
            }

            DB::commit();

            return $this->success(201, Lang::get('attendanceMessages.basic.SUCC_CREATE'), $newPostOtRequest);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    /**
     * Following function create attendance record seperatly
     *
     * @param $summeryRecord
     * @param $slot
     * @param $employeeId
     * @return | array
     *
     * Sample output: ['firstIn' => '2022-10-22 08:00:00', ...]
     */
    private function createAttendanceRecord($summeryRecord, $slot, $employeeId, $shiftData)
    {
        $inDateTime = $slot['inDate'] . ' ' . $slot['inTime'];
        $outDateTime = $slot['outDate'] . ' ' . $slot['outTime'];
        $timeZone = $summeryRecord['timeZone'];


        $inTime = $inDateTime;
        $outTime = $outDateTime;
        $inTimeDate = new DateTime($inTime, new DateTimeZone($timeZone));
        $outTimeDate = new DateTime($outTime, new DateTimeZone($timeZone));
        $inTimeDate->setTimezone(new DateTimeZone("UTC"));
        $outTimeDate->setTimezone(new DateTimeZone("UTC"));
        $inTimeUTC = $inTimeDate->format("Y-m-d H:i:s e");
        $outTimeUTC = $outTimeDate->format("Y-m-d H:i:s e");

        $expectedOut = new DateTime($summeryRecord['expectedOut'], new DateTimeZone($timeZone));
        $actualOut = new DateTime($outTime, new DateTimeZone($timeZone));
        $earlyCountInSec = $this->timeDiffSeconds($actualOut, $expectedOut, true);
        $earlyCountInMin = (int) ($earlyCountInSec / 60);

        // calculate lateIn
        $expectedIn = new DateTime($summeryRecord['expectedIn'], new DateTimeZone($timeZone));
        $actualIn = new DateTime($inTime, new DateTimeZone($timeZone));
        $lateCountInSec = $this->timeDiffSeconds($actualIn, $expectedIn, false);
        $lateCountInMin = (int) ($lateCountInSec / 60);
        $gracePeriod = (!is_null($shiftData) && !is_null($shiftData->gracePeriod)) ? (int) $shiftData->gracePeriod : 0;

        // calculate worked time
        $differenceInSec = $this->timeDiffSeconds($outTimeDate, $inTimeDate, false);
        $differenceInMin = (int) ($differenceInSec / 60);


        // insert new attendance
        $newAttendance = new stdClass();
        $newAttendance->date = $summeryRecord['date'];
        $newAttendance->in = $inTime;
        $newAttendance->inUTC = $inTimeUTC;
        $newAttendance->out = $outTime;
        $newAttendance->outUTC = $outTimeUTC;
        $newAttendance->typeId = 0;
        $newAttendance->employeeId = $employeeId;
        $newAttendance->calendarId = 0;
        $newAttendance->shiftId = $summeryRecord['shiftId'];
        $newAttendance->summaryId = $summeryRecord['summeryId'];
        $newAttendance->timeZone = $timeZone;
        $newAttendance->earlyOut = $earlyCountInMin;
        $newAttendance->lateIn =  $lateCountInMin > 0 && $lateCountInMin > $gracePeriod ? $lateCountInMin : 0;
        $newAttendance->workedHours = $differenceInMin;
        $newAttendance->breakHours = 0;

        $newAttendanceArray = (array) $newAttendance;
        $savedNewAttendance = $this->store->insert($this->attendanceModel, $newAttendanceArray, true);

        $summaryData = new stdClass();

        // set summary last in time
        $summaryData->firstIn = $actualIn->format("Y-m-d H:i:s");
        $summaryData->firstInUTC = Carbon::parse($actualIn, $timeZone)->copy()->tz('UTC')->format('Y-m-d H:i:s');
        $summaryData->lateIn = $lateCountInMin > 0 && $lateCountInMin > $gracePeriod ? $lateCountInMin : 0;

        // set summary last out time
        $summaryData->lastOut = $actualOut->format("Y-m-d H:i:s");
        $summaryData->lastOutUTC = Carbon::parse($actualOut, $timeZone)->copy()->tz('UTC')->format('Y-m-d H:i:s');
        $summaryData->isEarlyOut = $earlyCountInMin > 0;
        $summaryData->earlyOut = $earlyCountInMin;
        $summaryData->workTime = $differenceInMin;

        return $summaryData;
    }

    /**
     * Following function update and add breake records for perticular attendance 
     *
     * @return | array
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "update break records sucessfully!",
     *  */
    public function updateBreakRecordsAdmin($data)
    {
        try {
            DB::beginTransaction();
            $dataObject = (object) $data;
            $shiftId = $dataObject->shiftId;
            $employeeId =  $dataObject->employeeId;
            $shiftDate = $dataObject->shiftDate;
            $summaryId = $dataObject->summaryId;
            $breakDetails = $dataObject->breakDetails;
            unset($dataObject->breakDetails);

            $dates[] = $shiftDate;

            if (!$summaryId || !$employeeId || !$shiftDate) {
                throw new Exception("Missed at least one property");
            }

            // get attendance for that employee shift
            $attendancesSaved = $this->store->getFacade()::table('attendance')->where('employeeId', $employeeId)->where('summaryId', $summaryId)->orderBy("in", "asc")->get();

            if (is_null($attendancesSaved)) {
                return $this->error(502, Lang::get('attendanceMessages.basic.ERR_CREATE'));
            }


            if (!empty($breakDetails)) {

                $changeAttendanceRecords = [];

                foreach ($breakDetails as $breakKey => $breakRecord) {
                    $breakRecord = (array)  $breakRecord;

                    if ($breakRecord['id'] == 'new') {
                        $breakInDateTime = $breakRecord['breakInDate'] . ' ' . $breakRecord['breakInTime'];
                        $breakOutDateTime = $breakRecord['breakOutDate'] . ' ' . $breakRecord['breakOutTime'];

                        $breakInDateObj = Carbon::createFromFormat('Y-m-d H:i:s', $breakInDateTime);
                        $breakOutDateObj = Carbon::createFromFormat('Y-m-d H:i:s', $breakOutDateTime);

                        $selectedAttendanceId = null;

                        foreach ($attendancesSaved as $key => $attendance) {
                            $attendance = (array) $attendance;
                            $attendanceInObj = Carbon::createFromFormat('Y-m-d H:i:s', $attendance['in']);
                            $attendanceOutObj = Carbon::createFromFormat('Y-m-d H:i:s', $attendance['out']);

                            if (($breakInDateObj->between($attendanceInObj, $attendanceOutObj) && $breakOutDateObj->between($attendanceInObj, $attendanceOutObj))) {
                                $selectedAttendanceId = $attendance['id'];
                                $changeAttendanceRecords[] = $selectedAttendanceId;
                                break;
                            }
                        }

                        if (is_null($selectedAttendanceId)) {
                            DB::rollBack();
                            return $this->error(502, Lang::get('attendanceMessages.basic.ERR_CREATE'));
                        } else {

                            $diffInMin =  $breakInDateObj->diffInMinutes($breakOutDateObj, false);
                            $breakRecord = [
                                'attendanceId' => $selectedAttendanceId,
                                'in' => $breakInDateTime,
                                'out' => $breakOutDateTime,
                                'diff' => $diffInMin
                            ];
                            $savedNewBreak = $this->store->insert($this->breakModel, $breakRecord, true);
                            if (!$savedNewBreak) {
                                DB::rollBack();
                                return $this->error(502, Lang::get('attendanceMessages.basic.ERR_CREATE'), $savedNewBreak);
                            }
                        }
                    }
                }

                //update break time and work time in attendance record
                if (sizeof($changeAttendanceRecords) > 0) {
                    foreach ($changeAttendanceRecords as $attendanceKey => $attendenceId) {

                        $attendanceData = $this->store->getFacade()::table('attendance')->where('id', $attendenceId)->first();
                        $actualIn = $attendanceData->in;
                        $actualOut = $attendanceData->out;

                        $actualInObj = Carbon::parse($actualIn);
                        $actualOutObj = Carbon::parse($actualOut);

                        $totoalDiff =  $actualInObj->diffInMinutes($actualOutObj);

                        // get related breaks
                        $breaksSaved = $this->store->getFacade()::table('break')->where('attendanceId', $attendenceId)->get();

                        $totalBreakTime = 0;

                        foreach ($breaksSaved as $breakKey => $break) {
                            $break = (array) $break;
                            $totalBreakTime += (!empty($break['diff'])) ? $break['diff'] : 0;
                        }

                        $workedHours = $totoalDiff - $totalBreakTime;
                        $breakHours = $totalBreakTime;

                        $updateAttendance = [
                            'workedHours' => $workedHours,
                            'breakHours' => $breakHours
                        ];

                        $updatedAttendanceRecord = $this->store->updateById($this->attendanceModel, $attendenceId, (array) $updateAttendance, true);
                    }
                }

                $dataSet = [
                    'employeeId' => $employeeId,
                    'dates' => $dates
                ];

                event(new AttendanceDateDetailChangedEvent($dataSet));
            }

            DB::commit();
            $attendanceRecord = $this->getAttendanceDateData($employeeId, $shiftDate);

            return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), $attendanceRecord);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function getLastLoggedTime($employeeId)
    {
        try {
            $result = DB::table('attendance_summary')
                ->select(["actualIn", "actualOut"])
                ->where('attendance_summary.employeeId', '=', $employeeId)
                ->whereNotNull('attendance_summary.actualIn')
                ->orderBy('attendance_summary.date', 'DESC')
                ->first();

            $lastLoggedTime = null;

            if (!empty($result)) {
                $lastLoggedTime = !is_null($result->actualOut) ? $result->actualOut : $result->actualIn;
            }

            return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET'), ['lastLoggedTime' => $lastLoggedTime]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    private function timeDiffSeconds($punchedTime, $expectedTime, $invert)
    {
        $timeDifferent = $expectedTime->diff($punchedTime);

        return $timeDifferent->invert === ($invert ? 1 : 0) ? ($timeDifferent->h * 60 * 60 + $timeDifferent->i * 60 + $timeDifferent->s) : 0;
    }

    private function callAttendanceSummaryAdd($employeeId, $date)
    {
            // error_log(json_encode($summarySaved));
        $this->processEmployeeAttendance($employeeId, [$date]);
    }

    private function getAttendanceSummary($employeeId, $currentTime, $timeZone)
    {
        try {
            $date = $currentTime->format('Y-m-d');
            $summarySaved = $this->store->getFacade()::table('attendance_summary')->where('employeeId', $employeeId)->where('date', $date)->orderBy("expectedIn", "asc")->get();
            $associatedSummary = null;

            if (count($summarySaved) === 0) {
                $this->callAttendanceSummaryAdd($employeeId, $date);

                $summarySaved = $this->store->getFacade()::table('attendance_summary')->where('employeeId', $employeeId)->where('date', $date)->orderBy("expectedIn", "asc")->get();
            }

            $firstSummaryExpectedIn = $summarySaved[0]->expectedIn;
            $isExpectedInEmpty = $firstSummaryExpectedIn ? false : true;
            $firstSummaryActualIn = $summarySaved[0]->actualIn;
            $isActualInEmpty = $firstSummaryActualIn ? false : true;
            $currentTimeDate = new DateTime($currentTime);
            $isBeforeShiftStart = $currentTimeDate < new DateTime($firstSummaryExpectedIn);

            if ($isActualInEmpty || $isBeforeShiftStart || $isExpectedInEmpty) {
                $lastDay = date('Y-m-d', (strtotime('-1 day', strtotime($date))));
                $lastSummarySaved = $this->store->getFacade()::table('attendance_summary')->where('employeeId', $employeeId)->where('date', $lastDay)->orderBy("actualIn", "desc")->first();

                if (!$lastSummarySaved) {
                    $this->callAttendanceSummaryAdd($employeeId, $lastDay);

                    $lastSummarySaved = $this->store->getFacade()::table('attendance_summary')->where('employeeId', $employeeId)->where('date', $date)->orderBy("expectedIn", "desc")->first();
                }

                if ($lastSummarySaved) {
                    $lastAttendanceSaved = $this->store->getFacade()::table('attendance')->where('summaryId', $lastSummarySaved->id)->orderBy("in", "desc")->first();

                    $minutesToAdd = config('app.shift_threshold');
                    $shiftThreshold = "+$minutesToAdd minutes";

                    if ($lastAttendanceSaved) {
                        $lastExpectedOutTime = $lastSummarySaved->expectedOut;
                        $isExpectedOutNotEmpty = $lastExpectedOutTime ? true : false;

                        if ($isExpectedOutNotEmpty) {
                            $maximumWorkTimeStamp = strtotime($lastExpectedOutTime);

                            if ($shiftThreshold !== 0) {
                                $maximumWorkTimeStamp = strtotime($shiftThreshold, strtotime($lastExpectedOutTime));
                            }

                            $maximumWorkTime = new DateTime(date('Y-m-d H:i:sP', $maximumWorkTimeStamp));
                            $isWithInMaximumWorkTime = $maximumWorkTime > $currentTimeDate;

                            if ($isWithInMaximumWorkTime) {
                                return $lastSummarySaved;
                            }
                        }
                    }
                }
            }

            foreach ($summarySaved as $key => $summary) {
                if ($key === 0) {
                    $associatedSummary = $summary;
                } else if ($associatedSummary) {
                    $expectedSummaryTime = new DateTime($summary->expectedOut, new DateTimeZone($timeZone));
                    $expectedTimeAssociatedSummary = new DateTime($associatedSummary->expectedOut, new DateTimeZone($timeZone));
                    $currentTimeObj = $currentTime->toDateTime();
                    $isCurrentAfterAssociatedSummary = $currentTimeObj > $expectedTimeAssociatedSummary;
                    $isSummaryAfterAssociatedSummary = $expectedSummaryTime > $expectedTimeAssociatedSummary;
                    $isSummaryAfterCurrent = $expectedSummaryTime > $currentTimeObj;

                    if ($isCurrentAfterAssociatedSummary) {
                        if ($isSummaryAfterAssociatedSummary) {
                            $associatedSummary = $summary;
                        }
                    } else {
                        if ($isSummaryAfterCurrent && !$isSummaryAfterAssociatedSummary) {
                            $associatedSummary = $summary;
                        }
                    }
                }
            }
            return $associatedSummary;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function downloadTeamAttendance($request)
    {
        try {
            $employeeId = $request->query('employee', null);
            $fromDate = $request->query('fromDate', null);
            $toDate = $request->query('toDate', null);
            $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();

            $excelData = Excel::download(new AttendanceExcelExport($employeeId, $fromDate, $toDate, $permittedEmployeeIds), 'attendanceData.xlsx');
            $file = $excelData->getFile()->getPathname();
            $fileData = file_get_contents($file);
            unlink($file);

            return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_GET_FILE'), base64_encode($fileData));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    /* following funtion is to save data in attendance backup log
    *usage
    * $data =[
    *  {
    *     "id":1,
    *    "recNo":"1",
    *    "deviceId":"2",
    *    "empId":"1",
    *    "type":"Check in",
    *    "mode":"F ",
    *    "recordTimeString":"2021-05-11 03:20:51",
    *    "recordTime":"2021-05-11T03:20:51",
    *    "readDateTime":"1905-03-15T00:00:00",
    *   "client":"BHRCUS1005",
    *    "location":"LOC1"
    *  },
    *  {
    *   ...
    *   }
    * ];
    * Sample output:
    * [
    *      $statusCode => 200,
    *      $message => " Successfully saved!",
    *      $data => null
    * ]
    */
    public function createAttendanceLog($data) {
        try {
            DB::beginTransaction();
            $dataSet = collect($data)->map(function($item) {
               $item['recordId'] = $item['id'];
               $item['attendanceId'] = $item['empId'];
               unset($item['id'], $item['empId']);
               return $item;
            });
            
            // it will chunk the dataset in smaller collections containing 100 values each. 
            $chunks = $dataSet->chunk(100);
            foreach ($chunks as $chunk)
            {
                $attendanceLog = $this->store->getFacade()::table('attendance_backup_log')->insert($chunk->toArray());
            }

            $syncAttendance = $this->syncAttendanceToSystem();
            DB::commit();

            return $this->success(200, Lang::get('attendanceMessages.basic.SUCC_CREATE_ATTENDANCE_LOG'),  null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }


     /**
     * Following function retrive processed invalid attendance report records.
     *
     *
     * Sample output: $data => ['sheets' => [{}]]
     *
     */
    private function getInvalidAttendanceReportData($employeeId, $fromDate, $toDate, $pageNo, $pageCount, $sort, $entityId, $dataType, $reportType ,$isFromEmployee = false)
    {
        try {
            $whereQuery = '';
            $paginationQuery = '';
            $orderQuery = '';
            $whereInQuery = '';

            $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();

            if (is_null($employeeId) && empty($permittedEmployeeIds)) {
                return null;
            }

            if (count($permittedEmployeeIds) > 0) {
                $whereInQuery = "WHERE attendance_summary.employeeId IN (" . implode(",", $permittedEmployeeIds) . ")";

                if ($entityId) {
                    $entityIds = $this->getParentEntityRelatedChildNodes((int)$entityId);
                    array_push($entityIds, (int)$entityId);
                    $whereInQuery .= " AND employeeJob.orgStructureEntityId IN (" . implode(",", $entityIds) .")";
                }
            }

            if ($employeeId && $fromDate) {
                $whereQuery = "WHERE attendance_summary.employeeId = " . $employeeId . " AND  attendance_summary.date >= '" . $fromDate . "' AND attendance_summary.date <= '" . $toDate . "'";
            } else if ($employeeId) {
                $whereQuery = "WHERE attendance_summary.employeeId = " . $employeeId;
            } else if ($fromDate) {
                $clause = "attendance_summary.date >= '" . $fromDate . "' AND attendance_summary.date <= '" . $toDate . "'";
                $whereQuery = empty($whereInQuery) ? "WHERE " . $clause : $whereInQuery . " AND " . $clause;
            } else {
                $whereQuery = $whereInQuery;
            }


            $clause = "attendance.in IS NOT NULL AND attendance.out IS NULL";
            $whereQuery = empty($whereQuery) ? "WHERE " . $clause : $whereQuery . " AND " . $clause;

            if ($dataType === 'table' && $pageNo && $pageCount) {
                $skip = ($pageNo - 1) * $pageCount;
                $paginationQuery = "LIMIT " . $skip . ", " . $pageCount;
            }

            // if ($sort->name === 'name') {
            //     $orderQuery = "ORDER BY employeeName " . $sort->order . ", attendance_summary.date ASC";
            // } else if ($sort->name === 'date') {
            //     $orderQuery = "ORDER BY attendance_summary.date " . $sort->order;
            // }

            $queryCount = "
            SELECT
                COUNT(*) as dataCount
            FROM attendance_summary
            LEFT JOIN attendance on attendance_summary.id = attendance.summaryId
            LEFT JOIN employee on attendance_summary.employeeId = employee.id
            LEFT JOIN employeeJob on employee.currentJobsId = employeeJob.id
            LEFT JOIN workShifts ON workShifts.id = attendance_summary.shiftId
            LEFT JOIN time_change_requests on attendance_summary.id = time_change_requests.summaryId AND time_change_requests.type = 0
            {$whereQuery}
            GROUP BY attendance_summary.id
            ;
            ";

            $query = "
            SELECT
                attendance_summary.actualIn as firstIn,
                attendance_summary.actualOut as lastOut,
                attendance_summary.workTime as totalWorked,
                attendance_summary.breakTime as totalBreaks,
                attendance_summary.id,
                attendance_summary.date,
                attendance_summary.timeZone,
                attendance_summary.shiftId,
                attendance_summary.isPresent,
                attendance_summary.isExpectedToPresent,
                attendance_summary.expectedIn,
                attendance_summary.expectedOut,
                attendance_summary.isFullDayLeave,
                attendance_summary.isHalfDayLeave,
                attendance_summary.isShortLeave,
                workCalendarDayType.name,
                workShifts.name as shiftName,
                employee.id as employeeIdNo,
                employee.employeeNumber,
                CONCAT(employee.firstName, ' ', employee.lastName) as employeeName,
                attendance_summary.lateIn as lateTime,
                attendance_summary.earlyOut as earlyTime,
                time_change_requests.id as requestedTimeChangeId,
                workPattern.name as workPatternName
            FROM attendance_summary
                LEFT JOIN attendance on attendance_summary.id = attendance.summaryId
                LEFT JOIN employee on attendance_summary.employeeId = employee.id
                LEFT JOIN employeeJob on employeeJob.id = employee.currentJobsId
            	LEFT JOIN workShifts ON workShifts.id = attendance_summary.shiftId
                LEFT JOIN time_change_requests on attendance_summary.id = time_change_requests.summaryId AND time_change_requests.type = 0
                LEFT JOIN workPatternWeekDay ON workShifts.id = workPatternWeekDay.workShiftId
                LEFT JOIN workPatternWeek ON workPatternWeekDay.workPatternWeekId = workPatternWeek.id
                LEFT JOIN workPattern ON workPatternWeek.workPatternId = workPattern.id
                LEFT JOIN workCalendarDayType ON attendance_summary.dayTypeId = workCalendarDayType.id
            {$whereQuery}
            GROUP BY attendance_summary.id
            {$orderQuery}
            {$paginationQuery}
            ;
            ";

            $attendanceCount = DB::select($queryCount);
            $attendanceSheets = DB::select($query);
            $attendanceSheetsArray = [];

            $isMaintainOt = $this->getConfigValue('over_time_maintain_state');
            $sugessionRequireDates = [];

            foreach ($attendanceSheets as $key => $attendance) {
                $leaveData = null;

                $invalidAttendanceCount = DB::table('attendance')->where('summaryId', $attendance->id)->where('employeeId', $attendance->employeeIdNo)->where('date', $attendance->date)->whereNull('out')->count();

                if ($invalidAttendanceCount > 0) {
                    // if ($attendance->isFullDayLeave || $attendance->isHalfDayLeave ||  $attendance->isShortLeave) {
                    if ($attendance->isFullDayLeave || $attendance->isHalfDayLeave) {
                        $leave = $this->getEmployeeLeave($attendance->employeeIdNo, $attendance->date);

                        if (!empty($leave)) {
                            $leaveData = (array)$leave[0];
                        }
                    }

                    //get attendance slot details
                    $attendanceSlotDetails = $this->getAttendanceSlotDetails($attendance->id, $attendance->employeeIdNo, $attendance->date, $attendance->expectedIn, $attendance->expectedOut, $attendance->timeZone);

                    $leaves = [];

                    $inDate = $attendance->firstIn ? date('Y-m-d', strtotime($attendance->firstIn)) : null;
                    $outDate = $attendance->lastOut ? date('Y-m-d', strtotime($attendance->lastOut)) : null;
                    $isDifferentOutDate = $attendance->lastOut && date('Y-m-d', strtotime($attendance->date)) !== date('Y-m-d', strtotime($attendance->lastOut)) ? true : false;

                    $totalLate = '00:00';
                    if ($attendance->lateTime && $attendance->earlyTime) {
                        $totalLate = gmdate("H:i", ($attendance->earlyTime * 60 + $attendance->lateTime * 60));
                    } elseif ($attendance->lateTime && !$attendance->earlyTime) {
                        $totalLate = gmdate("H:i", ($attendance->lateTime * 60));
                    } elseif (!$attendance->lateTime && $attendance->earlyTime) {
                        $totalLate = gmdate("H:i", ($attendance->earlyTime * 60));
                    }

                    $attendanceItem = new stdClass();
                    $attendanceItem->id = $attendance->id;
                    $attendanceItem->date = $attendance->date;
                    $attendanceItem->employeeIdNo = $attendance->employeeIdNo;
                    $attendanceItem->employeeNumber = $attendance->employeeNumber;
                    $attendanceItem->name = $attendance->employeeName;
                    $attendanceItem->shift = is_null($attendance->shiftName) ? $attendance->workPatternName : $attendance->shiftName;
                    $attendanceItem->leave = $leaves;
                    $attendanceItem->dayType = $attendance->name;
                    $attendanceItem->inDate = $inDate;
                    $attendanceItem->inDateAndTimeSlot1 = $attendanceSlotDetails['slot1']['slot1InDateTime'];
                    $attendanceItem->outDateAndTimeSlot1 = $attendanceSlotDetails['slot1']['slot1OutDateTime'];
                    $attendanceItem->inDateAndTimeSlot2 = $attendanceSlotDetails['slot2']['slot2InDateTime'];
                    $attendanceItem->outDateAndTimeSlot2 = $attendanceSlotDetails['slot2']['slot2OutDateTime'];
                    $attendanceItem->incompleUpdate = false;
                    $attendanceItem->isChanged = false;
                    $attendanceItem->hasErrors = true;


                    array_push($attendanceSheetsArray, $attendanceItem);
                }
            }

            $responce = new stdClass();
            $responce->sheets = $attendanceSheetsArray;
            $responce->count =  count($attendanceCount);

            return $responce;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }


    private function getAttendanceReportData($employeeId, $fromDate, $toDate, $pageNo, $pageCount, $sort, $entityId, $dataType, $reportType,$isFromEmployee = false, $isForPostOTRequest = false)
    {
        try {
            $whereQuery = '';
            $paginationQuery = '';
            $orderQuery = '';
            $whereInQuery = '';

            $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();

            if (is_null($employeeId) && empty($permittedEmployeeIds)) {
                return null;
            }

            $empIdArray = [];
            if (!is_null($employeeId)) {
                array_push($empIdArray, $employeeId);
            } else {
                $empIdArray = $permittedEmployeeIds;
            }

            $relatedDayTypes = $this->getRelatedDayTypesByEmployee($empIdArray);

            if (count($permittedEmployeeIds) > 0) {
                $whereInQuery = "WHERE attendance_summary.employeeId IN (" . implode(",", $permittedEmployeeIds) . ")";

                if ($entityId) {
                    $entityIds = $this->getParentEntityRelatedChildNodes((int)$entityId);
                    array_push($entityIds, (int)$entityId);
                    $whereInQuery .= " AND employeeJob.orgStructureEntityId IN (" . implode(",", $entityIds) .")";
                }
            }

            if ($employeeId && $fromDate) {
                $whereQuery = "WHERE attendance_summary.employeeId = " . $employeeId . " AND  attendance_summary.date >= '" . $fromDate . "' AND attendance_summary.date <= '" . $toDate . "'";
            } else if ($employeeId) {
                $whereQuery = "WHERE attendance_summary.employeeId = " . $employeeId;
            } else if ($fromDate) {
                $clause = "attendance_summary.date >= '" . $fromDate . "' AND attendance_summary.date <= '" . $toDate . "'";
                $whereQuery = empty($whereInQuery) ? "WHERE " . $clause : $whereInQuery . " AND " . $clause;
            } else {
                $whereQuery = $whereInQuery;
            }

            if ($reportType == 'dailyAbsentWithoutLeave') {
                $clause = "attendance_summary.isExpectedToPresent = '" . true . "' AND attendance_summary.isPresent = '" . false . "' ";
                $whereQuery = empty($whereQuery) ? "WHERE " . $clause : $whereQuery . " AND " . $clause;
            }

            if ($reportType == 'dailyAbsentWithLeave') {
                // $clause = "attendance_summary.isExpectedToPresent = '" . true . "' AND attendance_summary.isPresent = '" . false . "' ";
                $clause = "attendance_summary.isFullDayLeave = '" . true . "' AND attendance_summary.isPresent = '" . false . "' ";
                $whereQuery = empty($whereQuery) ? "WHERE " . $clause : $whereQuery . " AND " . $clause;
            }

            if ($reportType == 'dailyLateHours') {
                // $clause = "attendance_summary.isExpectedToPresent = '" . true . "' AND attendance_summary.isPresent = '" . false . "' ";
                $clause = "(attendance_summary.isLateIn = '" . true . "' OR  attendance_summary.isEarlyOut = '" . true . "') ";
                $whereQuery = empty($whereQuery) ? "WHERE " . $clause : $whereQuery . " AND " . $clause;
            }


            if ($dataType == 'table') {
                if ($pageNo && $pageCount) {
                    $skip = ($pageNo - 1) * $pageCount;
                    $paginationQuery = "LIMIT " . $skip . ", " . $pageCount;
                }
            }


            // if ($sort->name === 'name') {
            //     $orderQuery = "ORDER BY employeeName " . $sort->order . ", attendance_summary.date ASC";
            // } else if ($sort->name === 'date') {
            // }
            // $orderQuery = "ORDER BY attendance_summary.date " . $sort->order;

            $queryCount = "
            SELECT
                COUNT(*) as dataCount
            FROM attendance_summary
            LEFT JOIN employee on attendance_summary.employeeId = employee.id
            left join employeeJob on employee.currentJobsId = employeeJob.id
            LEFT JOIN workShifts ON workShifts.id = attendance_summary.shiftId
            LEFT JOIN time_change_requests on attendance_summary.id = time_change_requests.summaryId AND time_change_requests.type = 0
            {$whereQuery}
            GROUP BY attendance_summary.id
            ;
            ";

            $query = "
            SELECT
                attendance_summary.actualIn as firstIn,
                attendance_summary.actualOut as lastOut,
                attendance_summary.workTime as totalWorked,
                attendance_summary.breakTime as totalBreaks,
                attendance_summary.id,
                attendance_summary.date,
                attendance_summary.timeZone,
                attendance_summary.shiftId,
                attendance_summary.dayTypeId,
                attendance_summary.isPresent,
                attendance_summary.isExpectedToPresent,
                attendance_summary.isFullDayLeave,
                attendance_summary.isHalfDayLeave,
                attendance_summary.isShortLeave,
                workCalendarDayType.name,
                workCalendarDayType.typeColor,
                workShifts.name as shiftName,
                employee.id as employeeIdNo,
                employee.employeeNumber,
                employeeJob.orgStructureEntityId,
                CONCAT(employee.firstName, ' ', employee.lastName) as employeeName,
                attendance_summary.lateIn as lateTime,
                attendance_summary.earlyOut as earlyTime,
                time_change_requests.id as requestedTimeChangeId,
                workPattern.name as workPatternName
            FROM attendance_summary
                LEFT JOIN employee on attendance_summary.employeeId = employee.id
                left join employeeJob on employeeJob.id = employee.currentJobsId
            	LEFT JOIN workShifts ON workShifts.id = attendance_summary.shiftId
                LEFT JOIN time_change_requests on attendance_summary.id = time_change_requests.summaryId AND time_change_requests.type = 0
                LEFT JOIN workPatternWeekDay ON workShifts.id = workPatternWeekDay.workShiftId
                LEFT JOIN workPatternWeek ON workPatternWeekDay.workPatternWeekId = workPatternWeek.id
                LEFT JOIN workPattern ON workPatternWeek.workPatternId = workPattern.id
                LEFT JOIN workCalendarDayType ON attendance_summary.dayTypeId = workCalendarDayType.id
            {$whereQuery}
            GROUP BY attendance_summary.id
            {$paginationQuery}
            ;
            ";

            $attendanceCount = DB::select($queryCount);
            $attendanceSheets = DB::select($query);
            $attendanceSheetsArray = [];
            $isMaintainOt = $this->getConfigValue('over_time_maintain_state');
            $sugessionRequireDates = [];

            foreach ($attendanceSheets as $key => $attendance) {
                $leaveData = [];
                // if ($attendance->isFullDayLeave || $attendance->isHalfDayLeave ||  $attendance->isShortLeave) {
                if ($attendance->isFullDayLeave || $attendance->isHalfDayLeave) {
                    $leave = $this->getEmployeeApprovedLeave($attendance->employeeIdNo, $attendance->date);

                    if (sizeof($leave) > 0) {
                        $leaveData = (array)$leave[0];
                    }
                }
                $leaves = [];
                if (sizeof($leaveData) > 0) {
                    if ($attendance->isFullDayLeave) {
                        $leave = new stdClass();
                        $leave->name = 'Full Day';
                        $leave->typeString = $leaveData['leaveTypeName'] . ' (' . $leaveData['entitlePortion'] . ')';
                        $leave->entitlePortion = $leaveData['entitlePortion'];
                        $leave->color = 'Red';
                        array_push($leaves, $leave);
                    }
                    if ($attendance->isHalfDayLeave) {
                        $leave = new stdClass();
                        $leave->typeString = $leaveData['leaveTypeName'] . ' (' . $leaveData['entitlePortion'] . ')';
                        $leave->entitlePortion = $leaveData['entitlePortion'];
                        $leave->name = 'Half Day';
                        $leave->color = 'Orange';
                        array_push($leaves, $leave);
                    }
                }
                // if ($attendance->isShortLeave) {
                //     $sortLeaveType = null;
                //     if ($leaveData['leavePeriodType'] == 'IN_SHORT_LEAVE') {
                //         $sortLeaveType = 'In';
                //     } else {
                //         $sortLeaveType = 'Out';
                //     }

                //     $leave = new stdClass();
                //     $leave->typeString = $leaveData['leaveTypeName'] . ' (' . $sortLeaveType . ')';
                //     $leave->name = 'Short';
                //     $leave->color = 'Yellow';
                //     array_push($leaves, $leave);
                // }

                $inDate = $attendance->firstIn ? date('Y-m-d', strtotime($attendance->firstIn)) : null;
                $outDate = $attendance->lastOut ? date('Y-m-d', strtotime($attendance->lastOut)) : null;
                $isDifferentOutDate = $attendance->lastOut && date('Y-m-d', strtotime($attendance->date)) !== date('Y-m-d', strtotime($attendance->lastOut)) ? true : false;

                $totalLate = '00:00';
                if ($attendance->lateTime && $attendance->earlyTime) {
                    $totalLate = gmdate("H:i", ($attendance->earlyTime * 60 + $attendance->lateTime * 60));
                } elseif ($attendance->lateTime && !$attendance->earlyTime) {
                    $totalLate = gmdate("H:i", ($attendance->lateTime * 60));
                } elseif (!$attendance->lateTime && $attendance->earlyTime) {
                    $totalLate = gmdate("H:i", ($attendance->earlyTime * 60));
                }

                $isBehaveAsNonWorkingDay = false;
                if (!is_null($attendance->shiftId) && !is_null($attendance->dayTypeId)) {
                    //get related workshift day type 
                    $relateWorkShiftDayTypeRecord = (array) DB::table('workShiftDayType')
                            ->where('workShiftId', $attendance->shiftId)
                            ->where('dayTypeId', $attendance->dayTypeId)->first();
                    $isBehaveAsNonWorkingDay = $relateWorkShiftDayTypeRecord['isBehaveAsNonWorkingDay'];
                }

                $attendanceItem = new stdClass();
                $attendanceItem->id = $attendance->id;
                $attendanceItem->date = $attendance->date;
                $attendanceItem->isExpectedToPresent = $attendance->isExpectedToPresent;
                $attendanceItem->expectedToPresent =  $attendance->isExpectedToPresent ? 'Yes' : 'No';
                $attendanceItem->outDate = (!empty($outDate)) ? $outDate : $attendance->date;
                $attendanceItem->employeeIdNo = $attendance->employeeIdNo;
                $attendanceItem->name = $attendance->employeeName;
                $attendanceItem->employeeNumber = $attendance->employeeNumber;
                $attendanceItem->shiftId = $attendance->shiftId;
                $attendanceItem->summaryId = $attendance->id;
                $attendanceItem->timeZone = $attendance->timeZone;
                $attendanceItem->shift = is_null($attendance->shiftName) ? $attendance->workPatternName : $attendance->shiftName;
                $attendanceItem->requestedTimeChangeId = $attendance->requestedTimeChangeId;
                $attendanceItem->leave = $leaves;
                $attendanceItem->day = new stdClass();
                $attendanceItem->day->isWorked = $attendance->isPresent;
                $attendanceItem->isPresentLabel = $attendance->isPresent ? 'Yes': 'No';
                $attendanceItem->day->dayType = $attendance->name;
                $attendanceItem->dayType = $attendance->name;
                $attendanceItem->day->dayTypeColor = $attendance->typeColor;
                $attendanceItem->day->isBehaveAsNonWorkingDay = $isBehaveAsNonWorkingDay;
                $attendanceItem->in = new stdClass();
                $attendanceItem->in->time = $attendance->firstIn ? date('h:i A', strtotime($attendance->firstIn)) : null;
                $attendanceItem->inTime = $attendance->firstIn ? date('h:i A', strtotime($attendance->firstIn)) : null;
                $attendanceItem->in->late = $attendance->lateTime ? gmdate("H:i", $attendance->lateTime * 60) : null;
                $attendanceItem->inLate = $attendance->lateTime ? gmdate("H:i", $attendance->lateTime * 60) : null;
                $attendanceItem->in->date = $inDate;
                $attendanceItem->inDate = $inDate;
                $attendanceItem->out = new stdClass();
                $attendanceItem->out->time = $attendance->lastOut ? date('h:i A', strtotime($attendance->lastOut)) : null;
                $attendanceItem->outTime = $attendance->lastOut ? date('h:i A', strtotime($attendance->lastOut)) : null;
                $attendanceItem->out->early = $attendance->earlyTime ?  gmdate("H:i", $attendance->earlyTime * 60): null;
                $attendanceItem->outEarly = $attendance->earlyTime ?  gmdate("H:i", $attendance->earlyTime * 60): null;
                $attendanceItem->out->date = $outDate;
                $attendanceItem->outDate = $outDate;
                $attendanceItem->leaveDetailString = !empty($leaves) ?  $leaves[0]->typeString : null;
                $attendanceItem->totalLate = $totalLate;
                $attendanceItem->out->isDifferentOutDate = $isDifferentOutDate;
                $attendanceItem->incompleUpdate = false;
                $attendanceItem->duration = new stdClass();
                $attendanceItem->duration->worked = gmdate("H:i", $attendance->totalWorked * 60);
                $attendanceItem->workedHours = gmdate("H:i", $attendance->totalWorked * 60);
                $attendanceItem->duration->workedMin = $attendance->totalWorked ? $attendance->totalWorked : 0;
                $attendanceItem->duration->breaks = $attendance->totalBreaks ? gmdate("H:i", $attendance->totalBreaks * 60) : null;

                //get sugesstions for time change  requests and leaves
                if ($isFromEmployee) {
                    if ($attendance->isExpectedToPresent && (empty($attendanceItem->in->time) || empty($attendanceItem->out->time)) && empty($attendanceItem->requestedTimeChangeId)) {
                        $sugessionRequireDates[] = Carbon::parse($attendance->date)->format('jS');
                    }
                }

                if ($reportType === 'dailyAbsentWithLeave') {
                    $attendanceItem->expectedToPresent = 'Yes';
                }

                $calculatedTotalOtMins = 0;
                $isRecordApprove = false;

                $attendanceItem->otData =  new stdClass();
                $attendanceItem->otData->totalOtHours = 0;
                $attendanceItem->totalOtHours = 0;
                $attendanceItem->otData->otDetails = [];
                $attendanceItem->otData->approvedOtDetails = [];
                $attendanceItem->otData->totalApprovedOtHours = 0;
                $attendanceItem->totalApprovedOtHours = 0;
                $attendanceItem->otData->requestedOtDetails = [];
                $attendanceItem->reason = null;

                //get related ot details
                if ($isMaintainOt) {
                    $relateOtRecords =  DB::table('attendanceSummaryPayTypeDetail')
                        ->leftJoin('payType', 'payType.id', '=', 'attendanceSummaryPayTypeDetail.payTypeId')
                        ->where('payType.type', '=', 'OVERTIME')
                        ->where('attendanceSummaryPayTypeDetail.summaryId', '=', $attendance->id)->get(['attendanceSummaryPayTypeDetail.id as attendanceSummaryPayTypeDetailId', 'payType.name', 'attendanceSummaryPayTypeDetail.workedTime', 'payType.code', 'attendanceSummaryPayTypeDetail.approvedWorkTime']);

                    $totalOtHoursData =  DB::table('attendanceSummaryPayTypeDetail')
                        ->leftJoin('payType', 'payType.id', '=', 'attendanceSummaryPayTypeDetail.payTypeId')
                        ->where('payType.type', '=', 'OVERTIME')
                        ->selectRaw('(sum(attendanceSummaryPayTypeDetail.workedTime)) as otHours')
                        ->groupBy('attendanceSummaryPayTypeDetail.summaryId')
                        ->where('attendanceSummaryPayTypeDetail.summaryId', '=', $attendance->id)->first();

                    if (!empty($totalOtHoursData)) {
                        $totalOtHours = gmdate("H:i", $totalOtHoursData->otHours * 60);
                        $calculatedTotalOtMins = $totalOtHoursData->otHours;
                    } else {
                        $totalOtHours = gmdate("H:i", 0 * 60);
                    }

                    $otDetails = [];
                    $approvedOtDetails = [];
                    $requestedOtDetails = [];
                    $totalApprovedOtHours = 0;
                    $reason = null;

                    $postOtRequestRecord = null;
                    $postOtRequestRecord =  DB::table('postOtRequestDetail')
                        ->where('summaryId', '=', $attendance->id)
                        ->whereIn('status', ['PENDING', 'APPROVED'])
                        ->first();

                    $attendanceItem->isApprovedOtAttendance  = (!is_null($postOtRequestRecord)) ? true : false;

                    $isRecordApprove = (!is_null($postOtRequestRecord) && $postOtRequestRecord->status == 'APPROVED') ? true : false;

                    if ($isForPostOTRequest) { 
                        //check whether ot is approved status for this attendance record
                        $otApprovedStatus = 'OPEN';
                        if (!is_null($postOtRequestRecord)) {
                            $otApprovedStatus = $postOtRequestRecord->status;
                            $reason = $postOtRequestRecord->requestedEmployeeComment;
                        }
                        $attendanceItem->otApprovedStatus = $otApprovedStatus;
                        
                    }

                    foreach ($relateOtRecords as $key => $otData) {
                        $otDetails[$otData->code] = gmdate("H:i", $otData->workedTime * 60);
                        $approvedOtDetails[$otData->code] = gmdate("H:i", $otData->approvedWorkTime * 60);
                        $totalApprovedOtHours += $otData->approvedWorkTime;
 
                        if (!is_null($postOtRequestRecord)) {
                            $ots = json_decode($postOtRequestRecord->otDetails);
                            $ots = (array) $ots;
                            $ots['requestedOtDetails'] = (array)$ots['requestedOtDetails'];
                            $timeArr = explode(':', $ots['requestedOtDetails'][$otData->code]);
                            $hoursIntoMin =  !empty($timeArr[0]) ? (int)$timeArr[0] * 60: 0;
                            $directMin = !empty($timeArr[1]) ?  (int)$timeArr[1] : 0;
                            $totolOtTime = $hoursIntoMin + $directMin;

                            $requestedOtDetails[$otData->code] = gmdate("H:i", $totolOtTime * 60);
                        } else {

                            $requestedOtDetails[$otData->code] = gmdate("H:i", $otData->workedTime * 60);
                        }

                    }

                    $attendanceItem->otData =  new stdClass();
                    $attendanceItem->otData->totalOtHours = $totalOtHours;
                    $attendanceItem->totalOtHours = $totalOtHours;
                    $attendanceItem->otData->otDetails = $otDetails;
                    $attendanceItem->otData->approvedOtDetails = $approvedOtDetails;
                    $attendanceItem->otData->totalApprovedOtHours = gmdate("H:i", $totalApprovedOtHours * 60);;
                    $attendanceItem->totalApprovedOtHours = gmdate("H:i", $totalApprovedOtHours * 60);;
                    $attendanceItem->otData->requestedOtDetails = $requestedOtDetails;
                    $attendanceItem->reason = $reason;
                }
                if (!$isForPostOTRequest) {
                    array_push($attendanceSheetsArray, $attendanceItem);
                } else {

                    //check has any pending time change requests
                    $pendingRequests = $this->store->getFacade()::table('time_change_requests')
                        ->leftJoin('workflowInstance', 'workflowInstance.id', '=', 'time_change_requests.workflowInstanceId')
                        ->where('time_change_requests.summaryId', $attendance->id)
                        ->where('workflowInstance.currentStateId', 1)
                        ->first();

                    if ($calculatedTotalOtMins > 0 && is_null($pendingRequests) && !$isRecordApprove) {
                        array_push($attendanceSheetsArray, $attendanceItem);
                    }
                }
            }

            $suggesionParagraph = null;
            if ($isFromEmployee && sizeof($sugessionRequireDates) > 0) {
                $sugesstionString = implode(', ',$sugessionRequireDates);
                $suggesionParagraph = 'You have incompleted records on '.$sugesstionString.'. Either apply for a leave or request for time change.';
            }

            $responce = new stdClass();
            $responce->count = (!$isForPostOTRequest) ?  count($attendanceCount) : sizeof($attendanceSheetsArray);
            $responce->sheets = $attendanceSheetsArray;
            $responce->isMaintainOt = $isMaintainOt;
            $responce->relatedDayTypes = $relatedDayTypes;
            $responce->suggesionParagraph = $suggesionParagraph;

            return $responce;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }


    public function getParentEntityRelatedChildNodes($id)
    {

        $items = $this->store->getFacade()::table("orgEntity")->where('isDelete', false)->get();
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


    private function getDailyOTReportData($employeeId, $fromDate, $toDate, $pageNo, $pageCount, $sort, $entityId, $dataType, $reportType,$isFromEmployee = false, $isForPostOTRequest = false)
    {
        try {
            $whereQuery = '';
            $paginationQuery = '';
            $orderQuery = '';
            $whereInQuery = '';

            $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();

            if (is_null($employeeId) && empty($permittedEmployeeIds)) {
                return null;
            }

            $empIdArray = [];
            if (!is_null($employeeId)) {
                array_push($empIdArray, $employeeId);
            } else {
                $empIdArray = $permittedEmployeeIds;
            }

            $relatedDayTypes = $this->getRelatedDayTypesByEmployee($empIdArray);

            if (count($permittedEmployeeIds) > 0) {
                $whereInQuery = "WHERE attendance_summary.employeeId IN (" . implode(",", $permittedEmployeeIds) . ") AND employee.isOTAllowed = true";

                if ($entityId) {
                    $entityIds = $this->getParentEntityRelatedChildNodes((int)$entityId);
                    array_push($entityIds, (int)$entityId);
                    $whereInQuery .= " AND employeeJob.orgStructureEntityId IN (" . implode(",", $entityIds) .")";
                }
            }

            if ($employeeId && $fromDate) {
                $whereQuery = "WHERE attendance_summary.employeeId = " . $employeeId . " AND  attendance_summary.date >= '" . $fromDate . "' AND attendance_summary.date <= '" . $toDate . "'";
            } else if ($employeeId) {
                $whereQuery = "WHERE attendance_summary.employeeId = " . $employeeId;
            } else if ($fromDate) {
                $clause = "attendance_summary.date >= '" . $fromDate . "' AND attendance_summary.date <= '" . $toDate . "'";
                $whereQuery = empty($whereInQuery) ? "WHERE " . $clause : $whereInQuery . " AND " . $clause;
            } else {
                $whereQuery = $whereInQuery;
            }

            if ($dataType == 'table') {
                if ($pageNo && $pageCount) {
                    $skip = ($pageNo - 1) * $pageCount;
                    $paginationQuery = "LIMIT " . $skip . ", " . $pageCount;
                }
            }


            // if ($sort->name === 'name') {
            //     $orderQuery = "ORDER BY employeeName " . $sort->order . ", attendance_summary.date ASC";
            // } else if ($sort->name === 'date') {
            // }
            // $orderQuery = "ORDER BY attendance_summary.date " . $sort->order;

            $queryCount = "
            SELECT
                COUNT(*) as dataCount
            FROM attendance_summary
            LEFT JOIN employee on attendance_summary.employeeId = employee.id
            left join employeeJob on employee.currentJobsId = employeeJob.id
            LEFT JOIN workShifts ON workShifts.id = attendance_summary.shiftId
            LEFT JOIN time_change_requests on attendance_summary.id = time_change_requests.summaryId AND time_change_requests.type = 0
            {$whereQuery}
            GROUP BY attendance_summary.id
            ;
            ";

            $query = "
            SELECT
                attendance_summary.actualIn as firstIn,
                attendance_summary.actualOut as lastOut,
                attendance_summary.workTime as totalWorked,
                attendance_summary.breakTime as totalBreaks,
                attendance_summary.id,
                attendance_summary.date,
                attendance_summary.timeZone,
                attendance_summary.shiftId,
                attendance_summary.dayTypeId,
                attendance_summary.isPresent,
                attendance_summary.isExpectedToPresent,
                attendance_summary.isFullDayLeave,
                attendance_summary.isHalfDayLeave,
                attendance_summary.isShortLeave,
                workCalendarDayType.name,
                workCalendarDayType.typeColor,
                workShifts.name as shiftName,
                employee.id as employeeIdNo,
                employee.employeeNumber,
                employeeJob.orgStructureEntityId,
                CONCAT(employee.firstName, ' ', employee.lastName) as employeeName,
                attendance_summary.lateIn as lateTime,
                attendance_summary.earlyOut as earlyTime,
                time_change_requests.id as requestedTimeChangeId,
                workPattern.name as workPatternName
            FROM attendance_summary
                LEFT JOIN employee on attendance_summary.employeeId = employee.id
                left join employeeJob on employeeJob.id = employee.currentJobsId
            	LEFT JOIN workShifts ON workShifts.id = attendance_summary.shiftId
                LEFT JOIN time_change_requests on attendance_summary.id = time_change_requests.summaryId AND time_change_requests.type = 0
                LEFT JOIN workPatternWeekDay ON workShifts.id = workPatternWeekDay.workShiftId
                LEFT JOIN workPatternWeek ON workPatternWeekDay.workPatternWeekId = workPatternWeek.id
                LEFT JOIN workPattern ON workPatternWeek.workPatternId = workPattern.id
                LEFT JOIN workCalendarDayType ON attendance_summary.dayTypeId = workCalendarDayType.id
            {$whereQuery}
            GROUP BY attendance_summary.id
            {$paginationQuery}
            ;
            ";

            $attendanceCount = DB::select($queryCount);
            $attendanceSheets = DB::select($query);
            $attendanceSheetsArray = [];
            $isMaintainOt = $this->getConfigValue('over_time_maintain_state');
            $sugessionRequireDates = [];

            foreach ($attendanceSheets as $key => $attendance) {
                $leaveData = [];
                // if ($attendance->isFullDayLeave || $attendance->isHalfDayLeave ||  $attendance->isShortLeave) {
                if ($attendance->isFullDayLeave || $attendance->isHalfDayLeave) {
                    $leave = $this->getEmployeeApprovedLeave($attendance->employeeIdNo, $attendance->date);

                    if (sizeof($leave) > 0) {
                        $leaveData = (array)$leave[0];
                    }
                }
                $leaves = [];
                if (sizeof($leaveData) > 0) {
                    if ($attendance->isFullDayLeave) {
                        $leave = new stdClass();
                        $leave->name = 'Full Day';
                        $leave->typeString = $leaveData['leaveTypeName'] . ' (' . $leaveData['entitlePortion'] . ')';
                        $leave->entitlePortion = $leaveData['entitlePortion'];
                        $leave->color = 'Red';
                        array_push($leaves, $leave);
                    }
                    if ($attendance->isHalfDayLeave) {
                        $leave = new stdClass();
                        $leave->typeString = $leaveData['leaveTypeName'] . ' (' . $leaveData['entitlePortion'] . ')';
                        $leave->entitlePortion = $leaveData['entitlePortion'];
                        $leave->name = 'Half Day';
                        $leave->color = 'Orange';
                        array_push($leaves, $leave);
                    }
                }
               
                $inDate = $attendance->firstIn ? date('Y-m-d', strtotime($attendance->firstIn)) : null;
                $outDate = $attendance->lastOut ? date('Y-m-d', strtotime($attendance->lastOut)) : null;
                $isDifferentOutDate = $attendance->lastOut && date('Y-m-d', strtotime($attendance->date)) !== date('Y-m-d', strtotime($attendance->lastOut)) ? true : false;

                $totalLate = '00:00';
                if ($attendance->lateTime && $attendance->earlyTime) {
                    $totalLate = gmdate("H:i", ($attendance->earlyTime * 60 + $attendance->lateTime * 60));
                } elseif ($attendance->lateTime && !$attendance->earlyTime) {
                    $totalLate = gmdate("H:i", ($attendance->lateTime * 60));
                } elseif (!$attendance->lateTime && $attendance->earlyTime) {
                    $totalLate = gmdate("H:i", ($attendance->earlyTime * 60));
                }

                $isBehaveAsNonWorkingDay = false;
                if (!is_null($attendance->shiftId) && !is_null($attendance->dayTypeId)) {
                    //get related workshift day type 
                    $relateWorkShiftDayTypeRecord = (array) DB::table('workShiftDayType')
                            ->where('workShiftId', $attendance->shiftId)
                            ->where('dayTypeId', $attendance->dayTypeId)->first();
                    $isBehaveAsNonWorkingDay = $relateWorkShiftDayTypeRecord['isBehaveAsNonWorkingDay'];
                }

                $attendanceItem = new stdClass();
                $attendanceItem->id = $attendance->id;
                $attendanceItem->date = $attendance->date;
                $attendanceItem->employeeIdNo = $attendance->employeeIdNo;
                $attendanceItem->name = $attendance->employeeName;
                $attendanceItem->employeeNumber = $attendance->employeeNumber;
                $attendanceItem->shift = is_null($attendance->shiftName) ? $attendance->workPatternName : $attendance->shiftName;
              
                $calculatedTotalOtMins = 0;
                $isRecordApprove = false;

                $attendanceItem->otData =  new stdClass();
                $attendanceItem->otData->totalOtHours = 0;
                $attendanceItem->totalOtHours = 0;
                $attendanceItem->otData->otDetails = [];
                $attendanceItem->otData->approvedOtDetails = [];
                $attendanceItem->otData->totalApprovedOtHours = 0;
                $attendanceItem->totalApprovedOtHours = 0;
                $attendanceItem->otData->requestedOtDetails = [];
                $attendanceItem->reason = null;


                $payTypesList =  DB::table('payType')
                        ->where('isDelete', false)
                        ->where('type', 'OVERTIME')
                        ->get();
                foreach ($payTypesList as $payTypeKey => $payTypeObj) {
                    $normalKey = $payTypeObj->code.'-count';
                    $approvedKey = $payTypeObj->code.'-approved-count';
                    $attendanceItem->$normalKey = '00:00';
                    $attendanceItem->$approvedKey = '00:00';
                }

                //get related ot details
                if ($isMaintainOt) {
                    $relateOtRecords =  DB::table('attendanceSummaryPayTypeDetail')
                        ->leftJoin('payType', 'payType.id', '=', 'attendanceSummaryPayTypeDetail.payTypeId')
                        ->where('payType.type', '=', 'OVERTIME')
                        ->where('attendanceSummaryPayTypeDetail.summaryId', '=', $attendance->id)->get(['attendanceSummaryPayTypeDetail.id as attendanceSummaryPayTypeDetailId', 'payType.name', 'attendanceSummaryPayTypeDetail.workedTime', 'payType.code', 'attendanceSummaryPayTypeDetail.approvedWorkTime']);

                    $totalOtHoursData =  DB::table('attendanceSummaryPayTypeDetail')
                        ->leftJoin('payType', 'payType.id', '=', 'attendanceSummaryPayTypeDetail.payTypeId')
                        ->where('payType.type', '=', 'OVERTIME')
                        ->selectRaw('(sum(attendanceSummaryPayTypeDetail.workedTime)) as otHours')
                        ->groupBy('attendanceSummaryPayTypeDetail.summaryId')
                        ->where('attendanceSummaryPayTypeDetail.summaryId', '=', $attendance->id)->first();

                    if (!empty($totalOtHoursData)) {
                        $totalOtHours = gmdate("H:i", $totalOtHoursData->otHours * 60);
                        $calculatedTotalOtMins = $totalOtHoursData->otHours;
                    } else {
                        $totalOtHours = gmdate("H:i", 0 * 60);
                    }

                    $otDetails = [];
                    $approvedOtDetails = [];
                    $requestedOtDetails = [];
                    $totalApprovedOtHours = 0;
                    $reason = null;

                    $postOtRequestRecord = null;
                    $postOtRequestRecord =  DB::table('postOtRequestDetail')
                        ->where('summaryId', '=', $attendance->id)
                        ->whereIn('status', ['PENDING', 'APPROVED'])
                        ->first();

                    $attendanceItem->isApprovedOtAttendance  = (!is_null($postOtRequestRecord)) ? true : false;

                    $isRecordApprove = (!is_null($postOtRequestRecord) && $postOtRequestRecord->status == 'APPROVED') ? true : false;

                    if ($isForPostOTRequest) { 
                        //check whether ot is approved status for this attendance record
                        $otApprovedStatus = 'OPEN';
                        if (!is_null($postOtRequestRecord)) {
                            $otApprovedStatus = $postOtRequestRecord->status;
                            $reason = $postOtRequestRecord->requestedEmployeeComment;
                        }
                        $attendanceItem->otApprovedStatus = $otApprovedStatus;
                        
                    }

                    foreach ($relateOtRecords as $key => $otData) {
                        $otDetails[$otData->code] = gmdate("H:i", $otData->workedTime * 60);
                        $approvedOtDetails[$otData->code] = gmdate("H:i", $otData->approvedWorkTime * 60);
                        $totalApprovedOtHours += $otData->approvedWorkTime;
 
                        if (!is_null($postOtRequestRecord)) {
                            $ots = json_decode($postOtRequestRecord->otDetails);
                            $ots = (array) $ots;
                            $ots['requestedOtDetails'] = (array)$ots['requestedOtDetails'];
                            $timeArr = explode(':', $ots['requestedOtDetails'][$otData->code]);
                            $hoursIntoMin =  !empty($timeArr[0]) ? (int)$timeArr[0] * 60: 0;
                            $directMin = !empty($timeArr[1]) ?  (int)$timeArr[1] : 0;
                            $totolOtTime = $hoursIntoMin + $directMin;

                            $requestedOtDetails[$otData->code] = gmdate("H:i", $totolOtTime * 60);
                        } else {

                            $requestedOtDetails[$otData->code] = gmdate("H:i", $otData->workedTime * 60);
                        }

                    }

                    $attendanceItem->otData =  new stdClass();
                    $attendanceItem->otData->totalOtHours = $totalOtHours;
                    $attendanceItem->totalOtHours = $totalOtHours;
                    $attendanceItem->otData->otDetails = $otDetails;
                    $attendanceItem->otData->approvedOtDetails = $approvedOtDetails;
                    $attendanceItem->otData->totalApprovedOtHours = gmdate("H:i", $totalApprovedOtHours * 60);;
                    $attendanceItem->totalApprovedOtHours = gmdate("H:i", $totalApprovedOtHours * 60);;
                    $attendanceItem->otData->requestedOtDetails = $requestedOtDetails;
                    $attendanceItem->reason = $reason;


                    foreach ($otDetails as $otobjkey => $otDataVal) {
                        $newNormalKey = $otobjkey.'-count';
                        $attendanceItem->$newNormalKey = $otDataVal;
                    }

                    foreach ($approvedOtDetails as $approvedOtObjKey => $approvedOtDataVal) {
                        $newApprovedKey = $approvedOtObjKey.'-approved-count';
                        $attendanceItem->$newApprovedKey = $approvedOtDataVal;
                    }
                }
                if (!$isForPostOTRequest) {
                    array_push($attendanceSheetsArray, $attendanceItem);
                } else {

                    //check has any pending time change requests
                    $pendingRequests = $this->store->getFacade()::table('time_change_requests')
                        ->leftJoin('workflowInstance', 'workflowInstance.id', '=', 'time_change_requests.workflowInstanceId')
                        ->where('time_change_requests.summaryId', $attendance->id)
                        ->where('workflowInstance.currentStateId', 1)
                        ->first();

                    if ($calculatedTotalOtMins > 0 && is_null($pendingRequests) && !$isRecordApprove) {
                        array_push($attendanceSheetsArray, $attendanceItem);
                    }
                }
            }

            $responce = new stdClass();
            $responce->count = (!$isForPostOTRequest) ?  count($attendanceCount) : sizeof($attendanceSheetsArray);
            $responce->sheets = $attendanceSheetsArray;
            $responce->isMaintainOt = $isMaintainOt;
            $responce->relatedDayTypes = $relatedDayTypes;

            return $responce;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }
}
