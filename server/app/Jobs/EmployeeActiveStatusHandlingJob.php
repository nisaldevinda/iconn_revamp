<?php

namespace App\Jobs;

use App\Library\Model;
use Illuminate\Support\Facades\DB;

class EmployeeActiveStatusHandlingJob extends AppJob
{
    protected $employeeModelName;
    protected $employeeRecordId;
    protected $employmentModelName;
    protected $employmentRecordId;
    protected $effectiveDate;
    protected $terminatedEmploymentStatusId;
    protected $tenantId;

    /**
     * Create a new EmployeeActiveStatusHandlingJob instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->tenantId = $data['tenantId'] ?? null;
        $this->employeeModelName = $data['parentModel'] ?? null;
        $this->employeeRecordId = $data['parentRecordId'] ?? null;
        $this->employmentModelName = $data['childModel'] ?? null;
        $this->employmentRecordId = $data['childRecordId'] ?? null;
        $this->effectiveDate = $data['effectiveDate'] ?? null;
        $this->terminatedEmploymentStatusId = $data['terminatedEmploymentStatusId'] ?? null;
    }

    /**
     * Execute the EmployeeActiveStatusHandlingJob.
     *
     * @return void
     */
    public function handle()
    {
        $this->setConnection($this->tenantId);

        $queueJobId = $this->job->getJobId();
        $jobRecord = DB::table('delayed_queue_job')
            ->where('queueJobId', $queueJobId)
            ->update(['isCancelled' => false]);

        $employmentRecord = DB::table($this->employmentModelName)
            ->where('id', $this->employmentRecordId)
            ->first();

        if (!empty($jobRecord) && $this->effectiveDate == $employmentRecord('effectiveDate')) {
            $data = [
                'isActive' => isset($employmentRecord['employmentStatusId'])
                    && $employmentRecord["employmentStatusId"] ==  $this->terminatedEmploymentStatusId ? false : true
            ];

            DB::table($this->employeeModelName)
                ->where('id', $this->employeeRecordId)
                ->update($data);
        }

        DB::table('delayed_queue_job')
            ->where('queueJobId', $queueJobId)
            ->delete();
    }
}
