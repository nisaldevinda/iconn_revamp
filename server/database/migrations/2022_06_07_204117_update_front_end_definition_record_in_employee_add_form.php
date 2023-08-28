<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFrontEndDefinitionRecordInEmployeeAddForm extends Migration
{
    private $id = 1;
    private $table = 'frontEndDefinition';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $updatedStructure = json_encode([
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
                    "hireDate",
                    "payGrade"
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
                "viewPermission" => "user-read-write",
                "editablePermission" => "user-read-write",
                "content" => [
                    "allowAccess",
                    "employeeRole",
                    "managerRole"
                ]
            ]
        ]);

        DB::table($this->table)
            ->where('id', $this->id)
            ->update(['structure' => $updatedStructure]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $oldStructure = json_encode([
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
                    "hireDate",
                    "payGrade"
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
                    "managerRole"
                ]
            ]
        ]);

        DB::table($this->table)
            ->where('id', $this->id)
            ->update(['structure' => $oldStructure]);
    }
}
