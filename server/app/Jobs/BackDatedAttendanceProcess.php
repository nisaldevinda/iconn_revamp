<?php

namespace App\Jobs;

use App\Traits\AttendanceProcess;
use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

class BackDatedAttendanceProcess extends AppJob
{

    use AttendanceProcess;

    protected $data;

    public $timeout = 3600;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // set tenant connection
            $this->setConnection($this->data['tenantId']);

            $dates = $this->data['dates'];
            $employeeIds = isset($this->data['employeeIds']) && is_array($this->data['employeeIds']) ? $this->data['employeeIds'] : [];
            $employeeId = isset($this->data['employeeId']) ? $this->data['employeeId'] : null;

            if (!empty($employeeIds)) {
                $this->processEmployeesAttendance($employeeIds, $dates);
            } else if (!empty($employeeId)) {
                $this->processEmployeeAttendance($employeeId, $dates);
            }

        } catch (Exception $e) {
            Log::error('failed' . $e->getMessage());
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $e)
    {
        Log::error('failed' . $e->getMessage());
    }
}
