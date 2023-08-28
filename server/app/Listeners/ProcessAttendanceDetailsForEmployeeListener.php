<?php

namespace App\Listeners;

use App\Events\ExampleEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Traits\AttendanceProcess;
use Illuminate\Support\Facades\DB;


class ProcessAttendanceDetailsForEmployeeListener implements ShouldQueue
{
    use AttendanceProcess;
    public $afterCommit = true;
    

    /**
     * Handle the event.
     *
     * @param  \App\Events\AttendanceDateDetailChangedEvent  $event
     * @return void
     */
    public function shouldQueue($event)
    {
        $employeeId = $event->dataSet['employeeId'];
        $dates = $event->dataSet['dates'];
    
        foreach ($dates as $date) {
            $dateArr = [];
            $dateArr[] = $date;

            $summaryRecord = DB::table('attendance_summary')->where('employeeId', $employeeId)->where('date', $date)->first();
    
            if (!empty($summaryRecord)) { // check if there is a summery record exsist for this particular date for this employee
                $processEmployeeAttendance = $this->processEmployeeAttendance($employeeId, $dateArr);
            }
        }
    }
}
