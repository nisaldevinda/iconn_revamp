<?php

namespace Tests\Feature;

use Laravel\Lumen\Testing\DatabaseMigrations;
use App\Library\Facades\Store;
use App\Library\JsonModelReader;
use Illuminate\Support\Facades\Artisan;
use App\Traits\EmployeeHelper;
use App\Library\Model;
use Tests\TestCase;

class PayRollAccessTokenRetriveTest extends TestCase
{
    static $isSetUpFreshDb = false;

    protected function setUp() : void
    {
        parent::setUp();
        if (!self::$isSetUpFreshDb) {
            Artisan::call('make:database-with-sample-data', ['dbname' => 'SampleDB']);
            self::$isSetUpFreshDb = true;
        }

    }


    /** @test */
    public function should_test_get_access_token()
    {
        
        $this->call('POST','/api/v1/oauth/token', [
            "grant_type" => "client_credentials",
            "scope" =>  "payroll",
            "client_id" =>  "1",
            "client_secret" => "R8sf54EliZjaXWbULG184jbVGJaxuwAHgUHSYlwR"
        ]);

        $this->seeStatusCode(200);

        $this->seeJsonStructure([
            "token_type",
            "expires_in",
            "access_token"
        ]);
        
    }


    /** @test */
    public function should_test_get_access_token_with_wrong_scope()
    {
        $this->call('POST','/api/v1/oauth/token', [
            "grant_type" => "client_credentials",
            "scope" =>  "test",
            "client_id" =>  "1",
            "client_secret" => "R8sf54EliZjaXWbULG184jbVGJaxuwAHgUHSYlwR"
        ]);

        $this->seeStatusCode(500);
        
    }

    /** @test */
    public function should_test_get_access_token_with_wrong_client_secret()
    {
        $this->call('POST','/api/v1/oauth/token', [
            "grant_type" => "client_credentials",
            "scope" =>  "payroll",
            "client_id" =>  "1",
            "client_secret" => "R8sf54EliZjaXWbULG184jbVuwAHgUHSYlwR"
        ]);

        $this->seeStatusCode(500);
        
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
