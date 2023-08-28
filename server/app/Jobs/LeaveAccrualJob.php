<?php

namespace App\Jobs;

use App\Traits\LeaveAccrual;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class LeaveAccrualJob extends AppJob
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
        try {
            // set tenant connection
            $this->setConnection($this->data['tenantId']);

            $date = $this->data['date'];

            $company = DB::table('company')->first(['timeZone']);

            $dateObject = Carbon::createFromFormat('Y-m-d', $date, $company->timeZone);

            $result = $this->accrualProcess($dateObject, true, $this->data['processId']);

            $data = ['status' => 'COMPLETED'];

            if ($result['error']) {
                $data = ['status' => 'ERROR', 'note' => $result['message']];
            }

            DB::table('manualProcess')->where('id', '=', $this->data['processId'])->update($data);
        } catch (Exception $e) {
            $data = ['status' => 'ERROR', 'note' => $e->getMessage()];
            DB::table('manualProcess')->where('id', '=', $this->data['processId'])->update($data);
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
        Log::error("LeaveAccrualJob error >>> " . $e->getMessage());
        $data = ['status' => 'ERROR', 'note' => $e->getMessage()];
        DB::table('manualProcess')->where('id', '=', $this->data['processId'])->update($data);
    }
}
