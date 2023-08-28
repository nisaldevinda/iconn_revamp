<?php

use Illuminate\Database\Migrations\Migration;

class InsertCompanyFrontendDefinition extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    
    private $table;
    private $data;

    public function __construct(){
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
                      "key" => "contact",
                      "defaultLabel" => "Contact",
                      "labelKey" => "EMPLOYEE.CONTACT",
                      "content" => [
                        "phone",
                        "fax",
                        "email",
                      ]
                    ],
                    [
                      "key" => "address",
                      "defaultLabel" => "Address",
                      "labelKey" => "EMPLOYEE.ADDRESS",
                      "content" => [
                        "street1",
                        "street2",
                        "country",
                        "zipCode",
                        "city",
                        "stateProvince",
                        "timeZone",
                        "notes"
                      ]
                    ],
                ])
            ],
        ];
    }


    public function up()
    {
        try {
            foreach (array_keys($this->data) as $id) {
                $record = DB::table($this->table)->where('id', $id)->first();

                if ($record) {
                    echo "{$this->table}, id = '{$id}' record already exists\n";
                    continue;
                }

                DB::table($this->table)->insert($this->data[$id]);
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
