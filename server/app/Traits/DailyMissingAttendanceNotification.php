<?php

namespace App\Traits;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\EmailNotificationJob;
use App\Library\Email;
trait DailyMissingAttendanceNotification
{
    use JobResponser;
    use EmployeeHelper;
    use ConfigHelper;

    public function processEmailForMissigAttendance($dateTimeObj)
    {
        try {

            $allowToSendMailForMissingAttendance = $this->getConfigValue('missing_attendance_email_notify_state');

            if (!$allowToSendMailForMissingAttendance) {
                return $this->jobResponse(false);
            }

            // get all locations
            $locations = DB::table('location')->get(['id', 'timeZone']);

            // get company timezone
            $company = DB::table('company')->first('timeZone');
            $locationRelateTimeZones = [];

            foreach ($locations as $locKey => $location) {
                $location = (array) $location;
                if (!in_array($location['timeZone'], $locationRelateTimeZones)) {
                    array_push($locationRelateTimeZones, $location['timeZone']);
                }
            }

            if (!in_array($company->timeZone, $locationRelateTimeZones)) {
                array_push($locationRelateTimeZones, $company->timeZone);
            }

            foreach ($locationRelateTimeZones as $timeZoneKey => $timeZoneString) {
                $timeZoneRelatedDateTimeObject = $dateTimeObj->copy()->tz($timeZoneString);
                // $timeZoneRelatedDateTimeObject = Carbon::parse('2023-07-20 07:00:00');

                $timeZoneRelatedDateTimeString = $timeZoneRelatedDateTimeObject->copy()->format('Y-m-d H:i:s');
                $timeZoneRelatedCurrentDate = $timeZoneRelatedDateTimeObject->copy()->format('Y-m-d');
                $timeZoneRelatedPreviousDate = $timeZoneRelatedDateTimeObject->copy()->subDay()->format('Y-m-d');
                $currentDateRelatedAlreadyNotifySummaryIds = DB::table('missingAttendanceNotificationLog')->where('attendanceDate', $timeZoneRelatedCurrentDate)->pluck('summaryId');
                $previousDateRelatedAlreadyNotifySummaryIds = DB::table('missingAttendanceNotificationLog')->where('attendanceDate', $timeZoneRelatedPreviousDate)->pluck('summaryId');

                $currentDateRelatedMissingAttendance = [];
                $currentDateRelatedPartiallyMissingAttendance = [];
                $currentDateRelatedAllMissingAttendance = [];

                $previousDateRelatedMissingAttendance = [];
                $previousDateRelatedPartiallyMissingAttendance = [];
                $previousDateRelatedAllMissingAttendance = [];

                $allMissingAttendanceRecords = [];

                //check and get the missing attendance records that related to the current date
                $currentDateAttendance = DB::table('attendance_summary')
                    ->leftJoin('employee', 'attendance_summary.employeeId', '=', 'employee.id')
                    ->where('attendance_summary.timeZone', $timeZoneString)
                    ->where('attendance_summary.date', $timeZoneRelatedCurrentDate)
                    ->where('attendance_summary.isExpectedToPresent', true)
                    ->where('attendance_summary.isPresent', false)
                    ->whereNotNull('attendance_summary.expectedIn')
                    ->whereNotNull('attendance_summary.expectedOut')
                    ->where('attendance_summary.expectedIn', '<', $timeZoneRelatedDateTimeString)
                    ->where('attendance_summary.expectedOut', '<=', $timeZoneRelatedDateTimeString)
                    ->whereNotIn('attendance_summary.id', $currentDateRelatedAlreadyNotifySummaryIds)->get(['attendance_summary.id', 'attendance_summary.employeeId','attendance_summary.date', 'attendance_summary.shiftId','attendance_summary.dayTypeId', 'attendance_summary.expectedIn', 'attendance_summary.expectedOut', 'attendance_summary.actualIn', 'employee.firstName', 'employee.lastName', 'employee.workEmail']);


                if (!empty($currentDateAttendance) && sizeof($currentDateAttendance) > 0) {
                    $currentDateRelatedMissingAttendance = collect($currentDateAttendance)->unique('id')->values();
                }

                //get current date related partially missing attendance
                $currentDatePartialyAttendance = DB::table('attendance_summary')
                    ->leftJoin('attendance', 'attendance_summary.id', '=', 'attendance.summaryId')
                    ->leftJoin('employee', 'attendance_summary.employeeId', '=', 'employee.id')
                    // ->whereNotNull('attendance.in')
                    ->whereNull('attendance.out')
                    ->where('attendance_summary.timeZone', $timeZoneString)
                    ->where('attendance_summary.date', $timeZoneRelatedCurrentDate)
                    ->where('attendance_summary.isExpectedToPresent', true)
                    ->where('attendance_summary.isPresent', true)
                    ->whereNotNull('attendance_summary.expectedIn')
                    ->whereNotNull('attendance_summary.expectedOut')
                    ->where('attendance_summary.expectedIn', '<', $timeZoneRelatedDateTimeString)
                    ->where('attendance_summary.expectedOut', '<=', $timeZoneRelatedDateTimeString)
                    ->whereNotIn('attendance_summary.id', $currentDateRelatedAlreadyNotifySummaryIds)->get(['attendance_summary.id', 'attendance_summary.employeeId', 'attendance_summary.date', 'attendance_summary.shiftId','attendance_summary.dayTypeId', 'attendance_summary.expectedIn', 'attendance_summary.expectedOut', 'attendance_summary.actualIn', 'employee.firstName', 'employee.lastName', 'employee.workEmail']);

                if (!empty($currentDatePartialyAttendance) && sizeof($currentDatePartialyAttendance) > 0) {
                    $currentDateRelatedPartiallyMissingAttendance = collect($currentDatePartialyAttendance)->unique('id')->values();
                }


                if (!empty($currentDateRelatedMissingAttendance) && !empty($currentDateRelatedPartiallyMissingAttendance)) {
                    $currentDateRelatedAllMissingAttendance = collect($currentDateRelatedMissingAttendance)->merge($currentDateRelatedPartiallyMissingAttendance)->unique('id')->values()->all();
                } elseif (empty($currentDateRelatedMissingAttendance) && !empty($currentDateRelatedPartiallyMissingAttendance)) {
                    $currentDateRelatedAllMissingAttendance = $currentDateRelatedPartiallyMissingAttendance;
                } elseif (!empty($currentDateRelatedMissingAttendance) && empty($currentDateRelatedPartiallyMissingAttendance)) {
                    $currentDateRelatedAllMissingAttendance = $currentDateRelatedMissingAttendance;
                }


                //check and get the missing attendance records that related to the current date
                $previousDateAttendance = DB::table('attendance_summary')
                    ->leftJoin('employee', 'attendance_summary.employeeId', '=', 'employee.id')
                    ->where('attendance_summary.timeZone', $timeZoneString)
                    ->where('attendance_summary.date', $timeZoneRelatedPreviousDate)
                    ->where('attendance_summary.isExpectedToPresent', true)
                    ->where('attendance_summary.isPresent', false)
                    ->whereNotNull('attendance_summary.expectedIn')
                    ->whereNotNull('attendance_summary.expectedOut')
                    ->where('attendance_summary.expectedIn', '<', $timeZoneRelatedDateTimeString)
                    ->where('attendance_summary.expectedOut', '<=', $timeZoneRelatedDateTimeString)
                    ->whereNotIn('attendance_summary.id', $previousDateRelatedAlreadyNotifySummaryIds)->get(['attendance_summary.id', 'attendance_summary.employeeId', 'attendance_summary.date', 'attendance_summary.shiftId','attendance_summary.dayTypeId', 'attendance_summary.expectedIn', 'attendance_summary.expectedOut', 'attendance_summary.actualIn', 'employee.firstName', 'employee.lastName', 'employee.workEmail']);

                if (!empty($previousDateAttendance) && sizeof($previousDateAttendance) > 0) {
                    $previousDateRelatedMissingAttendance = collect($previousDateAttendance)->unique('id')->values();
                }

                //get previous date related partailly missing Attendance
                $previousDatePartialyAttendance = DB::table('attendance_summary')
                    ->leftJoin('attendance', 'attendance_summary.id', '=', 'attendance.summaryId')
                    ->leftJoin('employee', 'attendance_summary.employeeId', '=', 'employee.id')
                    // ->whereNotNull('attendance.in')
                    ->whereNull('attendance.out')
                    ->where('attendance_summary.timeZone', $timeZoneString)
                    ->where('attendance_summary.date', $timeZoneRelatedPreviousDate)
                    ->where('attendance_summary.isExpectedToPresent', true)
                    ->where('attendance_summary.isPresent', true)
                    ->whereNotNull('attendance_summary.expectedIn')
                    ->whereNotNull('attendance_summary.expectedOut')
                    ->where('attendance_summary.expectedIn', '<', $timeZoneRelatedDateTimeString)
                    ->where('attendance_summary.expectedOut', '<=', $timeZoneRelatedDateTimeString)
                    ->whereNotIn('attendance_summary.id', $previousDateRelatedAlreadyNotifySummaryIds)->get(['attendance_summary.id', 'attendance_summary.employeeId', 'attendance_summary.date', 'attendance_summary.shiftId','attendance_summary.dayTypeId', 'attendance_summary.expectedIn', 'attendance_summary.expectedOut', 'attendance_summary.actualIn', 'employee.firstName', 'employee.lastName', 'employee.workEmail']);

                if (!empty($previousDatePartialyAttendance) && sizeof($previousDatePartialyAttendance) > 0) {
                    $previousDateRelatedPartiallyMissingAttendance = collect($previousDatePartialyAttendance)->unique('id')->values();
                }


                if (!empty($previousDateRelatedMissingAttendance) && !empty($previousDateRelatedPartiallyMissingAttendance)) {
                    $previousDateRelatedAllMissingAttendance = collect($previousDateRelatedMissingAttendance)->merge($previousDateRelatedPartiallyMissingAttendance)->unique('id')->values()->all();
                } elseif (empty($previousDateRelatedMissingAttendance) && !empty($previousDateRelatedPartiallyMissingAttendance)) {
                    $previousDateRelatedAllMissingAttendance = $previousDateRelatedPartiallyMissingAttendance;
                } elseif (!empty($previousDateRelatedMissingAttendance) && empty($previousDateRelatedPartiallyMissingAttendance)) {
                    $previousDateRelatedAllMissingAttendance = $previousDateRelatedMissingAttendance;
                }




                if (!empty($currentDateRelatedAllMissingAttendance) && !empty($previousDateRelatedAllMissingAttendance)) {
                    $allMissingAttendanceRecords = collect($currentDateRelatedAllMissingAttendance)->merge($previousDateRelatedAllMissingAttendance)->unique('id')->values()->all();
                } elseif (empty($currentDateRelatedAllMissingAttendance) && !empty($previousDateRelatedAllMissingAttendance)) {
                    $allMissingAttendanceRecords = $previousDateRelatedAllMissingAttendance;
                } elseif (!empty($currentDateRelatedAllMissingAttendance) && empty($previousDateRelatedAllMissingAttendance)) {
                    $allMissingAttendanceRecords = $currentDateRelatedAllMissingAttendance;
                }
               
                $filterdedAttendace = [];
                foreach ($allMissingAttendanceRecords as $attendanceKey => $attendanceRecord) {
                    $attendanceRecord = (array) $attendanceRecord;

                    //check whether attedance recod is fully missing attendance or partially missing attendance
                    if (!is_null($attendanceRecord['actualIn'])) {
                        //check whether current date time is greater than after adding buffer time to the expected out
                        $expectedOutObj = Carbon::parse($attendanceRecord['expectedOut']);
                        $bufferTimeInMin = $this->getConfigValue('missing_attendance_email_notify_buffer_time');
                        $extendedExpectedOutObj = $expectedOutObj->copy()->addMinutes($bufferTimeInMin);
                        
                        if ($timeZoneRelatedDateTimeObject->greaterThan($extendedExpectedOutObj)) {
                            array_push($filterdedAttendace, $attendanceRecord);
                        }

                    } else {
                        array_push($filterdedAttendace, $attendanceRecord);
                    }

                }

                $filterdedAttendace = collect($filterdedAttendace);
                $filterdedAttendaceChunks = $filterdedAttendace->chunk(100);

                foreach ($filterdedAttendaceChunks as $chunkKey => $attendanceChunk) {
                    $this->sendEmailsAttendanceToMissingEmployes($attendanceChunk);
                    sleep(1);
                }
               
            }

            return $this->jobResponse(false);
           
        } catch (Exception $e) {
            Log::error("Attendance process error : " . report($e));
            return $this->jobResponse(true, $e->getMessage());
        }
    }


    private function sendEmailsAttendanceToMissingEmployes($attendanceSet)
    {
        $notifyLogArray = [];
        foreach ($attendanceSet as $key => $attendanceRecord) {
            $attendanceRecord = (array) $attendanceRecord;
            $attendanceDate = $attendanceRecord['date'];

            $reportingManager =  $this->getManagerId($attendanceRecord['employeeId']);
            $ccEmailList = [];
            if (!is_null($reportingManager)) {
                $managerData = DB::table('employee')
                    ->select(['id','workEmail'])
                    ->where('id', $reportingManager)
                    ->where('isDelete', false)->first();

                if (!is_null($managerData)) {
                    $ccEmailList[] = $managerData->workEmail;
                }
            }
                       

            //set email body
            $emailBody = "You have missed marking the attendance and you need to regularize it for the day ".$attendanceDate.". This needs to be done with in 24 hours. Failing which will result in the Leave deductions as per the Leave policy schedule";
            $requestEmployeeName = $attendanceRecord['firstName'].' '.$attendanceRecord['lastName'];

            //send email for covering person
            $newEmail =  dispatch(new EmailNotificationJob(new Email('emails.missingAttendanceEmailContent', array($attendanceRecord['workEmail']), "Attendance Missing Notification", $ccEmailList, array("receipientFirstName" => $attendanceRecord['firstName'], "emailBody" => $emailBody))))->onQueue('email-queue');
        
            //add summary record to notification log

            $notifyLog = [
                'summaryId' => $attendanceRecord['id'],
                'attendanceDate' => $attendanceRecord['date'],
                'emailSentAt' => Carbon::now()->toDateTimeString()
            ];

            $notifyLogArray[] = $notifyLog; 

        }
        // create attendance process log
        $createNotifyLogs =  DB::table('missingAttendanceNotificationLog')->insert($notifyLogArray);
    }

    private function getManagerId($employeeId)
    {
        $employeeCurrentJob = DB::table('employee')->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
            ->where('employeeId', $employeeId)->first(['reportsToEmployeeId']);
        

        return is_null($employeeCurrentJob) ? null : $employeeCurrentJob->reportsToEmployeeId;
    }
}
