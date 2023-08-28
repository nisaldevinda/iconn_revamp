<?php

namespace App\Jobs;

use App\Traits\LeaveAccrual;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class LeaveAccrualBackdatedJob extends AppJob
{

    use LeaveAccrual;

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
        // set tenant connection
        $this->setConnection($this->data['tenantId']);

        $employeeId = $this->data['employeeId'];

        $hireDate = $this->data['hireDate'];

        $currentDate = $this->data['currentDate'];

        $this->backdatedAccrualProcess($employeeId, $hireDate, $currentDate);
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $e)
    {
        Log::error("LeaveAccrualBackdatedJob > " . $e->getMessage());
    }
}
