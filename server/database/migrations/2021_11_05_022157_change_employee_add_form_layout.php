<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeEmployeeAddFormLayout extends Migration
{
    private $table;
    private $upData;
    private $downData;

    public function __construct()
    {
        $this->table = 'frontEndDefinition';
        $this->upData = [
            1  => [
                "id" => 1,
                "modelName"  => "employee",
                "alternative"  => "add",
                "topLevelComponent"  => "section",
                "structure"  => json_encode([
                    [
                      "key" => "basicInformation",
                      "defaultLabel" => "Basic Information",
                      "labelKey" => "EMPLOYEE.BASIC_INFORMATION",
                      "content" => [
                        "employeeNumber",
                        "firstName",
                        "middleName",
                        "lastName",
                        "dateOfBirth",
                        "maritalStatus",
                        "gender"
                      ]
                    ],
                    [
                      "key" => "address",
                      "defaultLabel" => "Address",
                      "labelKey" => "EMPLOYEE.ADDRESS",
                      "content" => [
                        "permanentAddressStreet1",
                        "permanentAddressStreet2",
                        "permanentAddressCity",
                        "permanentAddressZip",
                        "permanentAddressCountry",
                        "permanentAddressState"
                      ]
                    ],
                    [
                      "key" => "contact",
                      "defaultLabel" => "Contact",
                      "labelKey" => "EMPLOYEE.ADDRESS",
                      "content" => [
                        "workEmail",
                        "personalEmail",
                        "workPhone",
                        "mobilePhone",
                        "homePhone"
                      ]
                    ],
                    [
                      "key" => "job",
                      "defaultLabel" => "Job",
                      "labelKey" => "EMPLOYEE.JOB",
                      "content" => [
                        "hireDate"
                      ]
                    ],
                    [
                      "key" => "employmentStatus",
                      "defaultLabel" => "Employment Status",
                      "labelKey" => "EMPLOYEE.EMPLOYMENT_STATUS",
                      "content" => [
                        "employments.employmentStatus"
                      ]
                    ],
                    [
                      "key" => "jobInformation",
                      "defaultLabel" => "Job Information",
                      "labelKey" => "EMPLOYEE.JOB_INFORMATION",
                      "content" => [
                        "jobs.jobTitle",
                        "jobs.reportsToEmployee",
                        "jobs.department",
                        "jobs.division",
                        "jobs.location"
                      ]
                    ],
                    [
                      "key" => "userAccess",
                      "defaultLabel" => "User Access",
                      "labelKey" => "EMPLOYEE.USER_ACCESS",
                      "content" => [
                        "allowAccess",
                        "employeeRole",
                        "managerRole",
                        "adminRole"
                      ]
                    ]
                ])
            ]
        ];
        $this->downData = [
            1  => [
                "id" => 1,
                "modelName"  => "employee",
                "alternative"  => "add",
                "topLevelComponent"  => "section",
                "structure"  => json_encode([
                    [
                      "key" => "basicInformation",
                      "defaultLabel" => "Basic Information",
                      "labelKey" => "EMPLOYEE.BASIC_INFORMATION",
                      "content" => [
                        "employeeNumber",
                        "firstName",
                        "middleName",
                        "lastName",
                        "dateOfBirth",
                        "maritalStatus",
                        "gender"
                      ]
                    ],
                    [
                      "key" => "address",
                      "defaultLabel" => "Address",
                      "labelKey" => "EMPLOYEE.ADDRESS",
                      "content" => [
                        "permanentAddressStreet1",
                        "permanentAddressStreet2",
                        "permanentAddressCity",
                        "permanentAddressZip",
                        "permanentAddressCountry",
                        "permanentAddressState"
                      ]
                    ],
                    [
                      "key" => "contact",
                      "defaultLabel" => "Contact",
                      "labelKey" => "EMPLOYEE.ADDRESS",
                      "content" => [
                        "workEmail",
                        "personalEmail",
                        "workPhone",
                        "mobilePhone",
                        "homePhone"
                      ]
                    ],
                    [
                      "key" => "job",
                      "defaultLabel" => "Job",
                      "labelKey" => "EMPLOYEE.JOB",
                      "content" => [
                        "hireDate"
                      ]
                    ],
                    [
                      "key" => "employmentStatus",
                      "defaultLabel" => "Employment Status",
                      "labelKey" => "EMPLOYEE.EMPLOYMENT_STATUS",
                      "content" => [
                        "employments.employmentStatus"
                      ]
                    ],
                    [
                      "key" => "jobInformation",
                      "defaultLabel" => "Job Information",
                      "labelKey" => "EMPLOYEE.JOB_INFORMATION",
                      "content" => [
                        "jobs.jobTitle",
                        "jobs.reportsToEmployee",
                        "jobs.department",
                        "jobs.division",
                        "jobs.location"
                      ]
                    ],
                    [
                      "key" => "userAccess",
                      "defaultLabel" => "User Access",
                      "labelKey" => "EMPLOYEE.USER_ACCESS",
                      "content" => [
                        "allowAccess",
                        "employeeRole",
                        "managerRole",
                        "adminRoles"
                      ]
                    ]
                ])
            ]
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
            foreach (array_keys($this->upData) as $id) {
                $record = DB::table($this->table)->where('id', $id)->first();

                if ($record) {
                    DB::table($this->table)
                        ->where('id', $id)
                        ->update($this->upData[$id]);
                    continue;
                }

                DB::table($this->table)->insert($this->upData[$id]);
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
            foreach (array_keys($this->downData) as $id) {
                $record = DB::table($this->table)->where('id', $id)->first();

                if ($record) {
                    DB::table($this->table)
                        ->where('id', $id)
                        ->update($this->downData[$id]);
                    continue;
                }

                DB::table($this->table)->insert($this->downData[$id]);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
