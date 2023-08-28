<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InsertFrontEndDefinitionData extends Migration
{
    private $table;
    private $data;

    public function __construct()
    {
        $this->table = 'frontEndDefinition';
        $this->data = [
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
            ],
            2  => [
                "id" => 2,
                "modelName"  => "employee",
                "alternative"  => "edit",
                "topLevelComponent"  => "tab",
                "structure"  => json_encode([
                    [
                        "key" => "personal",
                        "defaultLabel" => "Personal",
                        "labelKey" => "EMPLOYEE.PERSONAL",
                        "content" => [
                            [
                                "key" => "basicInformation",
                                "defaultLabel" => "Basic Information",
                                "labelKey" => "EMPLOYEE.PERSONAL.BASIC_INFORMATION",
                                "content" => [
                                    "employeeNumber",
                                    "initials",
                                    "firstName",
                                    "middleName",
                                    "lastName",
                                    "forename",
                                    "maidenName",
                                    "dateOfBirth",
                                    "maritalStatus",
                                    "gender",
                                    "bloodGroup"
                                ]
                            ],
                            [
                                "key" => "identificationInformation",
                                "defaultLabel" => "Identification Information",
                                "labelKey" => "EMPLOYEE.PERSONAL.IDENTIFICATION_INFORMATION",
                                "content" => [
                                  "nicNumber",
                                  "passportNumber",
                                  "passportExpiryDate",
                                  "drivingLicenceNumber"
                                ]
                            ],
                            [
                                "key" => "ethnicInformation",
                                "defaultLabel" => "Ethnic Information",
                                "labelKey" => "EMPLOYEE.PERSONAL.ETHNIC_INFORMATION",
                                "content" => [
                                    "religion",
                                    "nationality",
                                    "race"
                                ]
                            ],
                            [
                                "key" => "residentialAddress",
                                "defaultLabel" => "Residential Address",
                                "labelKey" => "EMPLOYEE.PERSONAL.RESIDENTIAL_ADDRESS",
                                "content" => [
                                    "residentialAddressStreet1",
                                    "residentialAddressStreet2",
                                    "residentialAddressCity",
                                    "residentialAddressZip",
                                    "residentialAddressCountry",
                                    "residentialAddressState"
                                ]
                            ],
                            [
                                "key" => "permanentAddress",
                                "defaultLabel" => "Permanent Address",
                                "labelKey" => "EMPLOYEE.PERSONAL.PERMANENT_ADDRESS",
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
                                "labelKey" => "EMPLOYEE.PERSONAL.CONTACT",
                                "content" => [
                                    "workEmail",
                                    "personalEmail",
                                    "workPhone",
                                    "mobilePhone",
                                    "homePhone"
                                ]
                            ],
                            [
                                "key" => "social",
                                "defaultLabel" => "Social",
                                "labelKey" => "EMPLOYEE.PERSONAL.SOCIAL",
                                "content" => [
                                    "facebookLink",
                                    "linkedIn",
                                    "twitter",
                                    "instagram",
                                    "pinterest"
                                ]
                            ]
                        ]
                    ],
                    [
                        "key" => "employment",
                        "defaultLabel" => "Employment",
                        "labelKey" => "EMPLOYEE.EMPLOYMENT",
                        "content" => [
                            [
                                "key" => "basic",
                                "defaultLabel" => "Basic",
                                "labelKey" => "EMPLOYEE.EMPLOYMENT.BASIC",
                                "content" => [
                                    "hireDate",
                                    "originalHireDate"
                                ]
                            ],
                            [
                                "key" => "employment",
                                "defaultLabel" => "Employment",
                                "labelKey" => "EMPLOYEE.EMPLOYMENT.EMPLOYMENT",
                                "content" => [
                                    "employments"
                                ]
                            ],
                            [
                                "key" => "job",
                                "defaultLabel" => "Job",
                                "labelKey" => "EMPLOYEE.EMPLOYMENT.JOB",
                                "content" => [
                                    "jobs"
                                ]
                            ]
                        ]
                    ],
                    [
                        "key" => "compensation",
                        "defaultLabel" => "Compensation",
                        "labelKey" => "EMPLOYEE.COMPENSATION",
                        "content" => [
                            [
                                "key" => "basic",
                                "defaultLabel" => "Basic",
                                "labelKey" => "EMPLOYEE.BASIC",
                                "content" => [
                                    "payGrade"
                                ]
                            ],
                            [
                                "key" => "salary",
                                "defaultLabel" => "Salary",
                                "labelKey" => "EMPLOYEE.SALARY",
                                "content" => [
                                    "salaries"
                                ]
                            ],
                            [
                                "key" => "bankAccount",
                                "defaultLabel" => "Bank Account",
                                "labelKey" => "EMPLOYEE.COMPENSATION.BANK_ACCOUNT",
                                "content" => [
                                    "bankAccounts"
                                ]
                            ]
                        ]
                    ],
                    [
                        "key" => "dependents",
                        "defaultLabel" => "Dependents",
                        "labelKey" => "EMPLOYEE.DEPENDENTS",
                        "content" => [
                            [
                                "key" => "dependents",
                                "defaultLabel" => "Dependents",
                                "labelKey" => "EMPLOYEE.DEPENDENTS.DEPENDENTS",
                                "content" => [
                                    "dependents"
                                ]
                            ],
                            [
                                "key" => "spouseDetails",
                                "defaultLabel" => "Spouse Details",
                                "labelKey" => "EMPLOYEE.DEPENDENTS.SPOUSE_DETAILS",
                                "content" => [
                                    "dateOfRegistration",
                                    "certificateNumber"
                                ]
                            ]
                        ]
                    ],
                    [
                        "key" => "workExperience",
                        "defaultLabel" => "Work Experience",
                        "labelKey" => "EMPLOYEE.WORK_EXPERIENCE",
                        "content" => [
                            [
                                "key" => "workExperience",
                                "defaultLabel" => "Work Experience",
                                "labelKey" => "EMPLOYEE.WORK_EXPERIENCE.WORK_EXPERIENCE",
                                "content" => [
                                    "experiences"
                                ]
                            ]
                        ]
                    ],
                    [
                        "key" => "qualification",
                        "defaultLabel" => "Qualification",
                        "labelKey" => "EMPLOYEE.QUALIFICATION",
                        "content" => [
                            [
                                "key" => "education",
                                "defaultLabel" => "Education",
                                "labelKey" => "EMPLOYEE.QUALIFICATION.EDUCATION",
                                "content" => [
                                    "educations"
                                ]
                            ],
                            [
                                "key" => "competencies",
                                "defaultLabel" => "Competencies",
                                "labelKey" => "EMPLOYEE.QUALIFICATION.COMPETENCIES",
                                "content" => [
                                    "competencies"
                                ]
                            ]
                        ]
                    ],
                    [
                        "key" => "emergency",
                        "defaultLabel" => "Emergency",
                        "labelKey" => "EMPLOYEE.EMERGENCY",
                        "content" => [
                            [
                                "key" => "emergencyContacts",
                                "defaultLabel" => "Emergency Contacts",
                                "labelKey" => "EMPLOYEE.EMERGENCY.EMERGENCY_CONTACTS",
                                "content" => [
                                    "emergencyContacts"
                                ]
                            ]
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
