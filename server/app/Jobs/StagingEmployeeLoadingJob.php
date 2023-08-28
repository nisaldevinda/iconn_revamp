<?php

namespace App\Jobs;

use App\Services\EmployeeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StagingEmployeeLoadingJob extends AppJob
{
    protected $data;

    /**
     * Create a new StagingEmployeeLoadingJob instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the StagingEmployeeLoadingJob.
     *
     * @return void
     */
    public function handle(EmployeeService $employeeService)
    {
        // set tenant connection
        $this->setConnection($this->data['tenantId']);

        foreach ($this->data['stagingEmployeeList'] as $id) {
            try {
                $stagingEmployeeRecord = DB::table('stagingEmployee')->where('sourceObjectId', $id)->first();
                $transformedEmployee = json_decode($stagingEmployeeRecord->transformedObject, true);
                $employee = $this->loadEmployee($transformedEmployee);

                $result = $employeeService->createEmployee($employee);

                if (isset($result['error']) && $result['error']) {
                    DB::table('stagingEmployee')
                        ->where('id', $id)
                        ->update([
                            'status' => 'ERROR',
                            'responseData' => $result
                        ]);

                    DB::table('employeeImportJob')
                        ->where('id', $this->data['employeeImportJobId'])
                        ->update([
                            'errorCount' => DB::raw('errorCount + 1')
                        ]);
                    continue;
                }

                DB::table('stagingEmployee')
                    ->where('id', $id)
                    ->update([
                        'userId' => $result['data']['user']['id'],
                        'employeeId' => $result['data']['id'],
                        'responseData' => null,
                        'status' => 'SUCCESS'
                    ]);

                DB::table('employeeImportJob')
                    ->where('id', $this->edata['mployeeImportJobId'])
                    ->update([
                        'successCount' => DB::raw('successCount + 1')
                    ]);

                echo $stagingEmployeeRecord->email . " has been imported successfully\n";
            } catch (\Throwable $th) {
                error_log($th);
                Log::error('Import azure user error -> ' . json_encode($th->getMessage()));
                DB::table('stagingEmployee')
                    ->where('id', $id)
                    ->update([
                        'status' => 'ERROR',
                        'responseData' => [
                            'error' => true,
                            'message' => 'Unknown error'
                        ]
                    ]);

                DB::table('employeeImportJob')
                    ->where('id', $this->data['employeeImportJobId'])
                    ->update([
                        'errorCount' => DB::raw('errorCount + 1')
                    ]);
                continue;
            }
        }
    }

    private function loadEmployee($transformedEmployee)
    {
        error_log($transformedEmployee);
    }
}
