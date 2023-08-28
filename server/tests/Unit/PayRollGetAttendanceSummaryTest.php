<?php

namespace Tests\Feature;

use Laravel\Lumen\Testing\DatabaseMigrations;
use App\Library\Facades\Store;
use App\Library\JsonModelReader;
use Illuminate\Support\Facades\Artisan;
use App\Traits\EmployeeHelper;
use App\Library\Model;
use Tests\TestCase;

class PayRollGetAttendanceSummaryTest extends TestCase
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
    public function should_test_get_attendance_summary()
    {
        
        $this->call('GET','/rest-api/get-employee-attendance-summery?from=2022-05-01&to=2022-05-31', [
        ], [], [], [
            "HTTP_Authorization" => "Bearer ".self::$accessToken
        ]);
        
        $this->seeStatusCode(200);
       
        self::$testSeqiesnce ++;
    }

    /** @test */
    public function test_get_employee_attendance_summary_with_wrong_access_token()
    {
        $this->call('GET','/rest-api/get-employee-attendance-summery?from=2022-05-01&to=2022-05-31', [
        ], [], [], [
            "HTTP_Authorization" => "Bearer "."test"
        ]);
        
        $this->seeStatusCode(500);

        self::$testSeqiesnce ++;
    }
 
    /** @test */
    public function test_get_employee_attendance_summary_with_page_number()
    {
        $this->call('GET','/rest-api/get-employee-attendance-summery?from=2022-05-01&to=2022-05-31&pageNo=1', [
        ], [], [], [
            "HTTP_Authorization" => "Bearer ".self::$accessToken
        ]);
        
        $content = json_decode($this->response->getContent());
        
        $responseEmployeeCount = sizeof($content->data->attendanceSummaryRecords);
        
        $this->seeStatusCode(200);
        $this->assertGreaterThan(0, $responseEmployeeCount); 

        self::$testSeqiesnce ++;
    }

    /** @test */
    public function test_get_employee_attendance_summary_has_correct_pay_cut_days()
    {
        $this->call('GET','/rest-api/get-employee-attendance-summery?from=2022-05-01&to=2022-05-31', [
        ], [], [], [
            "HTTP_Authorization" => "Bearer ".self::$accessToken
        ]);
        $this->seeStatusCode(200);

        $content = json_decode($this->response->getContent());
        $responseData = $content->data->attendanceSummaryRecords;
        $selectedEmployee = null;
        $employeeName = 'Hashan Hirosh';

        foreach ($responseData as $key => $employee) {
            $employee = (array) $employee;
            if ($employee['employeeFullName'] == $employeeName) {
                $selectedEmployee = $employee;
                break;
            }
        }

        $isSetEmployeeData = !empty($selectedEmployee) ? true : false;

        $this->assertTrue($isSetEmployeeData, $employeeName.' named employee not exsist');
        if ($this->assertTrue($isSetEmployeeData)) {
            $expectedPayCutDays = 6;
            $this->assertEquals(
                $expectedPayCutDays,
                $selectedEmployee['noOfDaysPayCut']
            );

        }
        self::$testSeqiesnce ++;
    }

    /** @test */
    public function test_get_employee_attendance_summary_has_set_ot_details()
    {
        $this->call('GET','/rest-api/get-employee-attendance-summery?from=2022-05-01&to=2022-05-31', [
        ], [], [], [
            "HTTP_Authorization" => "Bearer ".self::$accessToken
        ]);
        $this->seeStatusCode(200);

        $content = json_decode($this->response->getContent());
        $responseData = $content->data->attendanceSummaryRecords;
        $selectedEmployee = null;
        $employeeName = 'Tharindu Darshana';

        foreach ($responseData as $key => $employee) {
            $employee = (array) $employee;
            if ($employee['employeeFullName'] == $employeeName) {
                $selectedEmployee = $employee;
                break;
            }
        }

        $isSetEmployeeData = !empty($selectedEmployee) ? true : false;
        $this->assertTrue($isSetEmployeeData, $employeeName.' named employee not exsist');
 
        if ($this->assertTrue($isSetEmployeeData)) {
            $isSetOtDetails = (!empty($selectedEmployee['otDetails'])) ? true: false;
            $this->assertTrue($isSetOtDetails);

        }
         
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
