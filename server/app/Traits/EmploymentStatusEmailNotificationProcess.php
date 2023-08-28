<?php

namespace App\Traits;

use App\Jobs\EmailNotificationJob;
use App\Library\Email;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait EmploymentStatusEmailNotificationProcess
{
    use JobResponser;

    public function handleEmploymentStatusEmailNotificationForTenant($dateObj)
    {
        try {
            // get date by Y-m-d format
            $date = $dateObj->format('Y-m-d');

            // get tenant calenders
            $employees = DB::table('employee')
                ->leftJoin('employeeJob', 'employee.currentJobsId', '=', 'employeeJob.id')
                ->leftJoin('employee AS manager', 'employeeJob.reportsToEmployeeId', '=', 'manager.id')
                ->leftJoin('employmentStatus', 'employeeJob.employmentStatusId', '=', 'employmentStatus.id')
                ->whereNotNull('employee.currentJobsId')
                ->whereNotNull('employeeJob.employmentStatusId')
                ->whereNotNull('employmentStatus.allowEmploymentPeriod')
                ->whereNotNull('employmentStatus.period')
                ->whereNotNull('employmentStatus.periodUnit')
                ->whereNotNull('employmentStatus.enableEmailNotification')
                ->whereNotNull('employmentStatus.notificationPeriod')
                ->whereNotNull('employmentStatus.notificationPeriodUnit')
                ->where('employmentStatus.allowEmploymentPeriod', true)
                ->where('employmentStatus.enableEmailNotification', true)
                ->whereRaw('DATE_SUB(' .
                    'DATE_ADD(' .
                    'employeeJob.effectiveDate, ' .
                    'INTERVAL ' .
                    'employmentStatus.period ' .
                    'SUBSTRING(employmentStatus.periodUnit, 1, CHAR_LENGTH(employmentStatus.periodUnit)-1)' .
                    '), ' .
                    'INTERVAL ' .
                    'employmentStatus.notificationPeriod ' .
                    'SUBSTRING(employmentStatus.notificationPeriodUnit, 1, CHAR_LENGTH(employmentStatus.notificationPeriodUnit)-1)) ' .
                    '== ' . $date)
                ->get([
                    "CONCAT_WS(' ', firstName, middleName, lastName) AS employeeName",
                    "DATE_ADD(employeeJob.effectiveDate, INTERVAL employmentStatus.period SUBSTRING(employmentStatus.periodUnit, 1, CHAR_LENGTH(employmentStatus.periodUnit)-1)) AS employmentEndDate",
                    "employmentStatus.name AS employmentStatusName",
                    "employee.workEmail AS employeeWorkEmail",
                    "employee.personalEmail AS employeePersonalEmail",
                    "manager.workEmail AS managerWorkEmail",
                    "manager.workEmail AS managerPersonalEmail",
                ]);

            $successCount = 0;
            foreach ($employees as $employee) {
                $to = [];
                if (!empty($employee->managerWorkEmail)) $to[] = $employee->managerWorkEmail;
                if (!empty($employee->managerPersonalEmail)) $to[] = $employee->managerPersonalEmail;

                $cc = [];
                if (!empty($employee->employeeWorkEmail)) $cc[] = $employee->employeeWorkEmail;
                if (!empty($employee->employeePersonalEmail)) $cc[] = $employee->employeePersonalEmail;

                $data = [
                    'employeeName' => $employee->employeeName,
                    'employmentStatusName' => $employee->employmentStatusName,
                    'employmentEndDate' => $employee->employmentEndDate,
                ];

                $response =  dispatch(new EmailNotificationJob(new Email('emails.employmentNotificationEmail', $to, 'Upcoming Renewal of Employee Employment Status', $cc, $data)))->onQueue('email-queue');
                if ($response) ++$successCount; 
            }

            return $this->jobResponse(false, 'Successfully completed (' . $successCount . ' no of emails send)');
        } catch (Exception $e) {
            Log::error("Employment Status Email Notification Process Error : " . report($e));
            return $this->jobResponse(true, $e->getMessage());
        }
    }
}
