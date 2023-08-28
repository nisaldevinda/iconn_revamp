<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCompanyFrontendDefentionStructure extends Migration
{
    private $table;
    private $data;

    public function __construct()
    {
        $this->table = 'frontEndDefinition';
        $this->data = [
            3  => [
                "id" => 3,
                "modelName"  => "company",
                "alternative"  => null,
                "topLevelComponent"  => "section",
                "structure"  => json_encode([
                    [
                        "key" => "generalInformation",
                        "defaultLabel" => "General Information",
                        "labelKey" => "EMPLOYEE.GENERAL_INFORMATION",
                        "content" => [
                            "name",
                            "taxId",
                            "registrationNo",
                        ]
                    ],
                    [
                        "key" => "contact_and_address",
                        "defaultLabel" => "Contact and Address",
                        "labelKey" => "EMPLOYEE.CONTACT",
                        "content" => [
                            "phone",
                            "fax",
                            "email",
                            "street1",
                            "street2",
                            "country",
                            "zipCode",
                            "city",
                            "stateProvince",
                            "timeZone",
                            "notes"
                        ]
                    ]
                ])
            ],
        ];
    }

    /**
     * Run the migrations.
     *
     * @return void
     */


    public function up()
    {
        try {
            foreach (array_keys($this->data) as $id) {
                $record = DB::table($this->table)->where('id', $id)->first();

                if ($record) {
                    DB::table($this->table)->where('id', $id)->update($this->data[$id]);
                    continue;
                }
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        try {
            foreach (array_keys($this->data) as $id) {
                $record = DB::table($this->table)->where('id', $id)->first();

                if ($record) {
                    DB::table($this->table)->where('id', $id)->delete();
                }
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
