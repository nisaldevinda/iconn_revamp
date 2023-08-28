<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFrontEndDefinitions extends Migration
{
    private $id = 2;
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
                        "bloodGroup",
                    ],
                ],
                [
                    "key" => "identificationInformation",
                    "defaultLabel" => "Identification Information",
                    "labelKey" => "EMPLOYEE.PERSONAL.IDENTIFICATION_INFORMATION",
                    "content" => [
                        "nicNumber",
                        "passportNumber",
                        "passportExpiryDate",
                        "drivingLicenceNumber",
                    ],
                ],
                [
                    "key" => "ethnicInformation",
                    "defaultLabel" => "Ethnic Information",
                    "labelKey" => "EMPLOYEE.PERSONAL.ETHNIC_INFORMATION",
                    "content" => ["religion", "nationality", "race"],
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
                        "residentialAddressState",
                    ],
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
                        "permanentAddressState",
                    ],
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
                        "homePhone",
                    ],
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
                        "pinterest",
                    ],
                ],
            ],
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
                    "content" => ["hireDate", "originalHireDate"],
                ],
                [
                    "key" => "employment",
                    "defaultLabel" => "Employment",
                    "labelKey" => "EMPLOYEE.EMPLOYMENT.EMPLOYMENT",
                    "content" => ["employments"],
                ],
                [
                    "key" => "job",
                    "defaultLabel" => "Job",
                    "labelKey" => "EMPLOYEE.EMPLOYMENT.JOB",
                    "content" => ["jobs"],
                ],
            ],
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
                    "content" => ["payGrade"],
                ],
                [
                    "key" => "salary",
                    "defaultLabel" => "Salary",
                    "labelKey" => "EMPLOYEE.SALARY",
                    "content" => ["salaries"],
                ],
                [
                    "key" => "bankAccount",
                    "defaultLabel" => "Bank Account",
                    "labelKey" => "EMPLOYEE.COMPENSATION.BANK_ACCOUNT",
                    "content" => ["bankAccounts"],
                ],
            ],
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
                    "content" => ["dependents"],
                ],
                [
                    "key" => "spouseDetails",
                    "defaultLabel" => "Spouse Details",
                    "labelKey" => "EMPLOYEE.DEPENDENTS.SPOUSE_DETAILS",
                    "content" => ["dateOfRegistration", "certificateNumber"],
                ],
            ],
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
                    "content" => ["experiences"],
                ],
            ],
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
                    "content" => ["educations"],
                ],
                [
                    "key" => "competencies",
                    "defaultLabel" => "Competencies",
                    "labelKey" => "EMPLOYEE.QUALIFICATION.COMPETENCIES",
                    "content" => ["competencies"],
                ],
            ],
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
                    "content" => ["emergencyContacts"],
                ],
            ],
        ],
        [
            "key" => "documentTemplates",
            "defaultLabel" => "Document Templates",
            "labelKey" => "EMPLOYEE.DOCUMENT_TEMPLATES",
            "content" => [
                [
                    "key" => "documentTemplates",
                    "defaultLabel" => "Document Templates",
                    "labelKey" => "EMPLOYEE.DOCUMENT_TEMPLATES",
                    "content" => ["documentTemplates"],
                ],
            ],
        ],
        [
            "key" => "documents",
            "defaultLabel" => "Documents",
            "labelKey" => "EMPLOYEE.DOCUMENTS",
            "content" => [
                [
                    "key" => "documents",
                    "defaultLabel" => "Documents",
                    "labelKey" => "EMPLOYEE.DOCUMENTS",
                    "content" => ["documents"],
                ],
            ],
        ],
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
                          "bloodGroup",
                      ],
                  ],
                  [
                      "key" => "identificationInformation",
                      "defaultLabel" => "Identification Information",
                      "labelKey" => "EMPLOYEE.PERSONAL.IDENTIFICATION_INFORMATION",
                      "content" => [
                          "nicNumber",
                          "passportNumber",
                          "passportExpiryDate",
                          "drivingLicenceNumber",
                      ],
                  ],
                  [
                      "key" => "ethnicInformation",
                      "defaultLabel" => "Ethnic Information",
                      "labelKey" => "EMPLOYEE.PERSONAL.ETHNIC_INFORMATION",
                      "content" => ["religion", "nationality", "race"],
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
                          "residentialAddressState",
                      ],
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
                          "permanentAddressState",
                      ],
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
                          "homePhone",
                      ],
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
                          "pinterest",
                      ],
                  ],
              ],
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
                      "content" => ["hireDate", "originalHireDate"],
                  ],
                  [
                      "key" => "employment",
                      "defaultLabel" => "Employment",
                      "labelKey" => "EMPLOYEE.EMPLOYMENT.EMPLOYMENT",
                      "content" => ["employments"],
                  ],
                  [
                      "key" => "job",
                      "defaultLabel" => "Job",
                      "labelKey" => "EMPLOYEE.EMPLOYMENT.JOB",
                      "content" => ["jobs"],
                  ],
              ],
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
                      "content" => ["payGrade"],
                  ],
                  [
                      "key" => "salary",
                      "defaultLabel" => "Salary",
                      "labelKey" => "EMPLOYEE.SALARY",
                      "content" => ["salaries"],
                  ],
                  [
                      "key" => "bankAccount",
                      "defaultLabel" => "Bank Account",
                      "labelKey" => "EMPLOYEE.COMPENSATION.BANK_ACCOUNT",
                      "content" => ["bankAccounts"],
                  ],
              ],
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
                      "content" => ["dependents"],
                  ],
                  [
                      "key" => "spouseDetails",
                      "defaultLabel" => "Spouse Details",
                      "labelKey" => "EMPLOYEE.DEPENDENTS.SPOUSE_DETAILS",
                      "content" => ["dateOfRegistration", "certificateNumber"],
                  ],
              ],
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
                      "content" => ["experiences"],
                  ],
              ],
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
                      "content" => ["educations"],
                  ],
                  [
                      "key" => "competencies",
                      "defaultLabel" => "Competencies",
                      "labelKey" => "EMPLOYEE.QUALIFICATION.COMPETENCIES",
                      "content" => ["competencies"],
                  ],
              ],
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
                      "content" => ["emergencyContacts"],
                  ],
              ],
          ],
          [
              "key" => "documentTemplates",
              "defaultLabel" => "Document Templates",
              "labelKey" => "EMPLOYEE.DOCUMENT_TEMPLATES",
              "content" => [
                  [
                      "key" => "documentTemplates",
                      "defaultLabel" => "Document Templates",
                      "labelKey" => "EMPLOYEE.DOCUMENT_TEMPLATES",
                      "content" => ["documentTemplates"],
                  ],
              ],
          ],
          [
              "key" => "documents",
              "defaultLabel" => "Documents",
              "labelKey" => "EMPLOYEE.DOCUMENTS",
              "content" => [
                  [
                      "key" => "documents",
                      "defaultLabel" => "Documents",
                      "labelKey" => "EMPLOYEE.DOCUMENTS",
                      "content" => ["documents"],
                  ],
              ],
          ],
        ]);

        DB::table($this->table)
            ->where('id', $this->id)
            ->update(['structure' => $oldStructure]);
    }
}
