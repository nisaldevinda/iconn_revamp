<?php

namespace Tests\Feature;

use Laravel\Lumen\Testing\DatabaseMigrations;
use App\Library\Facades\Store;
use App\Library\JsonModelReader;
use Illuminate\Support\Facades\Artisan;
use App\Traits\EmployeeHelper;
use App\Library\Model;
use Tests\TestCase;

class PayRollUpdateAttendanceSummaryStateTest extends TestCase
{
    static $isSetUpFreshDb = false;
    static $testSeqiesnce = 0;
    static $accessToken = null;

    protected function setUp() : void
    {
        parent::setUp();
    
        if (!self::$isSetUpFreshDb) {
            Artisan::call('make:database-with-sample-data', ['dbname' => 'SampleDB']);
            self::$isSetUpFreshDb = true;
        }

        if (self::$testSeqiesnce == 0) {
            $this->generateAccessToken();
        }

    }

    /** @test */
    public function should_test_lock_attendance_summary_records()
    {
        $this->call('PUT','/rest-api/change-attedance-record-state', [
            "from" => "2022-05-01",
            "to" => "2022-05-31",
            "state" => "lock"
        ], [], [], [
            "HTTP_Authorization" => "Bearer ".self::$accessToken
        ]);
        
        $this->seeStatusCode(200);
        self::$testSeqiesnce ++;
    }

    /** @test */
    public function should_test_unlock_attendance_summary_records()
    {
        
        $this->call('PUT','/rest-api/change-attedance-record-state', [
            "from" => "2022-05-01",
            "to" => "2022-05-31",
            "state" => "unlock"
        ], [], [], [
            "HTTP_Authorization" => "Bearer ".self::$accessToken
        ]);
        
        $this->seeStatusCode(200);
        self::$testSeqiesnce ++;
    }

    /** @test */
    public function should_test_state_change_attendance_summary_records_with_wrong_date_range()
    {
        $this->call('PUT','/rest-api/change-attedance-record-state', [
            "from" => "2022-06-01",
            "to" => "2022-05-31",
            "state" => "lock"
        ], [], [], [
            "HTTP_Authorization" => "Bearer ".self::$accessToken
        ]);
        
        $this->seeStatusCode(500);
        self::$testSeqiesnce ++;
    }


    public function generateAccessToken()
    {
        $this->call('POST','/api/v1/oauth/token', [
            "grant_type" => "client_credentials",
            "scope" =>  "payroll",
            "client_id" =>  "1",
            "client_secret" => "R8sf54EliZjaXWbULG184jbVGJaxuwAHgUHSYlwR"
        ]);

        $content = json_decode($this->response->getContent());

        self::$accessToken = $content->access_token;
    }

}
