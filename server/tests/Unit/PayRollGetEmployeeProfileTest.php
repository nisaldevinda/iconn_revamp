<?php

namespace Tests\Feature;

use Laravel\Lumen\Testing\DatabaseMigrations;
use App\Library\Facades\Store;
use App\Library\JsonModelReader;
use Illuminate\Support\Facades\Artisan;
use App\Traits\EmployeeHelper;
use App\Library\Model;
use Tests\TestCase;

class PayRollGetEmployeeProfileTest extends TestCase
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
    public function should_test_get_employee_profiles()
    {
        $this->call('GET','/rest-api/get-employee-profiles', [
        ], [], [], [
            "HTTP_Authorization" => "Bearer ".self::$accessToken
        ]);
        
        $this->seeStatusCode(200);
        self::$testSeqiesnce ++;
    }

    /** @test */
    public function should_test_get_employee_profiles_with_wrong_access_token()
    {
        $this->call('GET','/rest-api/get-employee-profiles', [
        ], [], [], [
            "HTTP_Authorization" => "Bearer "."test"
        ]);
        
        $this->seeStatusCode(500);
        self::$testSeqiesnce ++;
    }

    /** @test */
    public function test_get_employee_profiles_with_page_number()
    {
        $this->call('GET','/rest-api/get-employee-profiles?pageNo=1', [
        ], [], [], [
            "HTTP_Authorization" => "Bearer ".self::$accessToken
        ]);
        
        $content = json_decode($this->response->getContent());
        $responseEmployeeCount = sizeof($content->data->employees);
        
        $this->seeStatusCode(200);
        $this->assertGreaterThan(0, $responseEmployeeCount); 

        self::$testSeqiesnce ++;
    }

    /** @test */
    public function should_test_get_employee_profiles_with_last_data_sync_datetime()
    {
     
        $this->call('GET','/rest-api/get-employee-profiles?lastDataSyncTimeStamp=2022-08-14 06:50:00.000', [
        ], [], [], [
            "HTTP_Authorization" => "Bearer ".self::$accessToken
        ]);

        $content = json_decode($this->response->getContent());
        $expectedCount = 1;
        
        $this->seeStatusCode(200);

        $this->assertCount(
            $expectedCount,
            $content->data->employees, "employees array should contain only one record"
        );

        $actualTimeStamp = strtotime('2022-08-14 06:50:00.000');
        $responseUpdateAt = strtotime($content->data->employees[0]->updatedAt);

        $this->assertGreaterThan( 
            $actualTimeStamp, 
            $responseUpdateAt
        ); 


        self::$testSeqiesnce ++;
    }

    /** @test */
    public function test_get_employee_profile_includes_salary_details()
    {
        $this->call('GET','/rest-api/get-employee-profiles?pageNo=1', [
        ], [], [], [
            "HTTP_Authorization" => "Bearer ".self::$accessToken
        ]);
         
        $this->seeStatusCode(200);

        $content = json_decode($this->response->getContent());
        $responseData = $content->data->employees;
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
            $isSetSalaryDetails = (!empty($selectedEmployee['salaryDetails'])) ? true : false;
            $this->assertTrue($isSetSalaryDetails, 'Salary details not set correctly');
        }

        self::$testSeqiesnce ++;
    }

    /** @test */
    public function test_get_employee_profile_includes_bank_details()
    {
        $this->call('GET','/rest-api/get-employee-profiles?pageNo=1', [
        ], [], [], [
            "HTTP_Authorization" => "Bearer ".self::$accessToken
        ]);
         
        $this->seeStatusCode(200);

        $content = json_decode($this->response->getContent());
        $responseData = $content->data->employees;
        $selectedEmployee = null;
        $employeeName = 'Aruna Dinesh Karunathilake';

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
            $isSetBankDetails = (!empty($selectedEmployee['bankDetails'])) ? true : false;     
            $this->assertTrue($isSetBankDetails, 'Bank details not set correctly');
        }

        self::$testSeqiesnce ++;
    }

    /** @test */
    public function test_get_employee_profile_resign_date_set_correctly()
    {
        $this->call('GET','/rest-api/get-employee-profiles?pageNo=1', [
        ], [], [], [
            "HTTP_Authorization" => "Bearer ".self::$accessToken
        ]);
         
        $this->seeStatusCode(200);

        $content = json_decode($this->response->getContent());
        $responseData = $content->data->employees;
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

            $isSetResignDateCorrectly = (!empty($selectedEmployee['dateOfResign'])) ? true : false;
            $expectedResignDate = "2022-05-25";
            $expectedEmployeementType = "Terminated";
    
            
            $this->assertTrue($isSetResignDateCorrectly);
            $this->assertEquals(
                $expectedEmployeementType,
                $selectedEmployee['employeeEmploymentType']
            );
    
            $this->assertEquals(
                $expectedResignDate,
                $selectedEmployee['dateOfResign']
            );
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
