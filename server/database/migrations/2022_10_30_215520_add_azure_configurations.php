<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAzureConfigurations extends Migration
{
    private $configuration = [
        [
            "id" => 4,
            "key" => "azure_tenant_id",
            "description" => "Azure Tenant ID",
            "type" => "json",
            "value" => null
        ],
        [
            "id" => 5,
            "key" => "azure_client_id",
            "description" => "Azure Client ID",
            "type" => "json",
            "value" => null
        ],
        [
            "id" => 6,
            "key" => "azure_client_secret",
            "description" => "Azure Client Secret",
            "type" => "json",
            "value" => null
        ],
        [
            "id" => 7,
            "key" => "is_active_azure_user_provisioning",
            "description" => "Activate Azure User Provisioning",
            "type" => "boolean",
            "value" => null
        ],
        [
            "id" => 8,
            "key" => "azure_domain_name",
            "description" => "Azure Domain Name",
            "type" => "json",
            "value" => null
        ],
        [
            "id" => 9,
            "key" => "azure_default_password",
            "description" => "Azure Default Password",
            "type" => "json",
            "value" => null
        ]
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('configuration')) {
            foreach ($this->configuration as $value) {
                DB::table('configuration')->insert($value);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('configuration')) {
            foreach ($this->configuration as $value) {
                $record = DB::table('configuration')->where('id', $value['id'])->get();
                if (!is_null($record)) {
                    $delete = DB::table('configuration')->where('id', $value['id'])->delete();
                }
            }
        }
    }
}
