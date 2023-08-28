<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateContactInformationToReportData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $contactInformationReport= DB::table('reportData')->where('id', '=', 3)->first();

        if (!empty($contactInformationReport)) {
           
            $selectedTables = json_encode([
                [
                    'key' => 'employeeNumber',
                    'isDerived' => false,
                    'tableName' => 'employee',
                    'originalTableName' => 'employee',
                    'parentColumnName' => 'employeeNumber',
                    'columnName' => 'employeeNumber',
                    'displayName' => 'Employee Number',
                    'columnIndex' => 0,
                    "valueType"=> "employeeNumber",
                    'dataIndex' => 'employeeemployeeNumber',
                ],
                [
                    'key' => 'firstName',
                    'isDerived' => false,
                    'tableName' => 'employee',
                    'originalTableName' => 'employee',
                    'parentColumnName' => 'firstName',
                    'columnName' => 'firstName',
                    'displayName' => 'First Name',
                    'columnIndex' => 1,
                    'valueType' => 'text',
                    'dataIndex' => 'employeefirstName',
                ],
                [
                    'key' => 'lastName',
                    'isDerived' => false,
                    'tableName' => 'employee',
                    'originalTableName' => 'employee',
                    'parentColumnName' => 'lastName',
                    'columnName' => 'lastName',
                    'displayName' => 'Last Name',
                    'columnIndex' => 2,
                    'valueType' => 'text',
                    'dataIndex' => 'employeelastName',
                ],
                [
                    'key' => 'workEmail',
                    'isDerived' => false,
                    'tableName' => 'employee',
                    'originalTableName' => 'employee',
                    'parentColumnName' => 'workEmail',
                    'columnName' => 'workEmail',
                    'displayName' => 'Work Email',
                    'columnIndex' => 3,
                    'valueType' => 'text',
                    'dataIndex' => 'employeeworkEmail',
                ],
                [
                    'key' => 'personalEmail',
                    'isDerived' => false,
                    'tableName' => 'employee',
                    'originalTableName' => 'employee',
                    'parentColumnName' => 'personalEmail',
                    'columnName' => 'personalEmail',
                    'displayName' => 'Personal Email',
                    'columnIndex' => 4,
                    'valueType' => 'text',
                    'dataIndex' => 'employeepersonalEmail',
                ],
                [
                    'key' => 'workPhone',
                    'isDerived' => false,
                    'tableName' => 'employee',
                    'originalTableName' => 'employee',
                    'parentColumnName' => 'workPhone',
                    'columnName' => 'workPhone',
                    'displayName' => 'Work-phone',
                    'columnIndex' => 5,
                    'valueType' => 'text',
                    'dataIndex' => 'employeeworkPhone',
                ],
                [
                    'key' => 'mobilePhone',
                    'isDerived' => false,
                    'tableName' => 'employee',
                    'originalTableName' => 'employee',
                    'parentColumnName' => 'mobilePhone',
                    'columnName' => 'mobilePhone',
                    'displayName' => 'Mobile-phone',
                    'columnIndex' => 6,
                    'valueType' => 'text',
                    'dataIndex' => 'employeemobilePhone',
                ],
                [
                    'key' => 'homePhone',
                    'isDerived' => false,
                    'tableName' => 'employee',
                    'originalTableName' => 'employee',
                    'parentColumnName' => 'homePhone',
                    'columnName' => 'homePhone',
                    'displayName' => 'Home-phone',
                    'columnIndex' => 7,
                    'valueType' => 'text',
                    'dataIndex' => 'employeehomePhone',
                ],
                
                
            ]);
            DB::table('reportData')->where('id', 3)->update(['selectedTables' => $selectedTables]);
        }
        
        $employmentHistoryReport = DB::table('reportData')->where('id', '=', 3)->first();

        if (!empty(  $employmentHistoryReport )) {
            $selectedTables = json_encode(
                [
                    [
                      'key' => 'employeeNumber',
                      'isDerived' => false,
                      'tableName' => 'employee',
                      'originalTableName' => 'employee',
                      'parentColumnName' => 'employeeNumber',
                      'columnName' => 'employeeNumber',
                      'displayName' => 'Employee Number',
                      'columnIndex' => 0,
                      'valueType' => 'employeeNumber',
                      'dataIndex' => 'employeeemployeeNumber',
                    ],
                    [
                      'key' => 'firstName',
                      'isDerived' => false,
                      'tableName' => 'employee',
                      'originalTableName' => 'employee',
                      'parentColumnName' => 'firstName',
                      'columnName' => 'firstName',
                      'displayName' => 'First Name',
                      'columnIndex' => 1,
                      'valueType' => 'text',
                      'dataIndex' => 'employeefirstName',
                    ],
                     [
                      'key' => 'lastName',
                      'isDerived' => false,
                      'tableName' => 'employee',
                      'originalTableName' => 'employee',
                      'parentColumnName' => 'lastName',
                      'columnName' => 'lastName',
                      'displayName' => 'Last Name',
                      'columnIndex' => 2,
                      'valueType' => 'text',
                      'dataIndex' => 'employeelastName',
                    ],
                    [
                      'key' => 'hireDate',
                      'isDerived' => false,
                      'tableName' => 'employee',
                      'originalTableName' => 'employee',
                      'parentColumnName' => 'hireDate',
                      'columnName' => 'hireDate',
                      'displayName' => 'Hire Date',
                      'columnIndex' => 3,
                      'valueType' => 'timestamp',
                      'dataIndex' => 'employeehireDate',
                    ],
                     [
                      'key' => 'employments.effectiveDate',
                      'isDerived' => false,
                      'tableName' => 'employeeEmployment',
                      'originalTableName' => 'employeeEmployment',
                      'parentColumnName' => 'effectiveDate',
                      'columnName' => 'effectiveDate',
                      'displayName' => 'Employments Effective Date',
                      'columnIndex' => 4,
                      'valueType' => 'timestamp',
                      'dataIndex' => 'employeeEmploymenteffectiveDate',
                    ],
                    [
                      'key' => 'employments.employmentStatus',
                      'isDerived' => false,
                      'tableName' => 'employmentsemploymentStatus',
                      'originalTableName' => 'employeeEmployment',
                      'parentColumnName' => 'employmentStatus',
                      'columnName' => 'name',
                      'displayName' => 'Employments Employment Status',
                      'columnIndex' => 5,
                      'valueType' => 'model',
                      'dataIndex' => 'employmentsemploymentStatusname',
                    ],
                    [
                      'key' => 'employments.comment',
                      'isDerived' => false,
                      'tableName' => 'employeeEmployment',
                      'originalTableName' => 'employeeEmployment',
                      'parentColumnName' => 'comment',
                      'columnName' => 'comment',
                      'displayName' => 'Employments Comment',
                      'columnIndex' => 6,
                      'valueType' => 'text',
                      'dataIndex' => 'employeeEmploymentcomment',
                    ],
                ]
            );
            $joinCriterias =json_encode([
              [
                'tableOneName' => 'employee',
                'tableOneAlias' => 'employee',
                'tableOneOperandOne' => 'id',
                'tableOneOperandTwo' => 'currentEmploymentsId',
                'operator' => '=',
                'tableTwoName' => 'employeeEmployment',
                'tableTwoAlias' => 'employeeEmployment',
                'tableTwoOperandOne' => 'employeeId',
                'tableTwoOperandTwo' => 'id',
              ],
              [
                'tableOneName' => 'employeeEmployment',
                'tableOneAlias' => 'employeeEmployment',
                'tableOneOperandOne' => 'employmentStatusId',
                'operator' => '=',
                'tableTwoName' => 'employmentStatus',
                'tableTwoAlias' => 'employmentsemploymentStatus',
                'tableTwoOperandOne' => 'id',
              ]

            ]);   
            DB::table('reportData')->where('id', 4)->update(['selectedTables' => $selectedTables , 'joinCriterias' =>  $joinCriterias]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $contactInformationReport = DB::table('reportData')->where('id', '=', 3)->first();

        if (!empty($contactInformationReport)) {
            $selectedTables = json_encode([
                [
                    'key' => 'workEmail',
                    'isDerived' => false,
                    'tableName' => 'employee',
                    'originalTableName' => 'employee',
                    'parentColumnName' => 'workEmail',
                    'columnName' => 'workEmail',
                    'displayName' => 'Work Email',
                    'columnIndex' => 0,
                    'valueType' => 'text',
                    'dataIndex' => 'employeeworkEmail',
                ],
                [
                    'key' => 'personalEmail',
                    'isDerived' => false,
                    'tableName' => 'employee',
                    'originalTableName' => 'employee',
                    'parentColumnName' => 'personalEmail',
                    'columnName' => 'personalEmail',
                    'displayName' => 'Personal Email',
                    'columnIndex' => 1,
                    'valueType' => 'text',
                    'dataIndex' => 'employeepersonalEmail',
                ],
                [
                    'key' => 'workPhone',
                    'isDerived' => false,
                    'tableName' => 'employee',
                    'originalTableName' => 'employee',
                    'parentColumnName' => 'workPhone',
                    'columnName' => 'workPhone',
                    'displayName' => 'Work-phone',
                    'columnIndex' => 2,
                    'valueType' => 'text',
                    'dataIndex' => 'employeeworkPhone',
                ],
                [
                    'key' => 'mobilePhone',
                    'isDerived' => false,
                    'tableName' => 'employee',
                    'originalTableName' => 'employee',
                    'parentColumnName' => 'mobilePhone',
                    'columnName' => 'mobilePhone',
                    'displayName' => 'Mobile-phone',
                    'columnIndex' => 3,
                    'valueType' => 'text',
                    'dataIndex' => 'employeemobilePhone',
                ],
                [
                    'key' => 'homePhone',
                    'isDerived' => false,
                    'tableName' => 'employee',
                    'originalTableName' => 'employee',
                    'parentColumnName' => 'homePhone',
                    'columnName' => 'homePhone',
                    'displayName' => 'Home-phone',
                    'columnIndex' => 4,
                    'valueType' => 'text',
                    'dataIndex' => 'employeehomePhone',
                ],
                [
                    'key' => 'lastName',
                    'isDerived' => false,
                    'tableName' => 'employee',
                    'originalTableName' => 'employee',
                    'parentColumnName' => 'lastName',
                    'columnName' => 'lastName',
                    'displayName' => 'Last Name',
                    'columnIndex' => 5,
                    'valueType' => 'text',
                    'dataIndex' => 'employeelastName',
                ],
                [
                    'key' => 'employeeNumber',
                    'isDerived' => false,
                    'tableName' => 'employee',
                    'originalTableName' => 'employee',
                    'parentColumnName' => 'employeeNumber',
                    'columnName' => 'employeeNumber',
                    'displayName' => 'Employee Number',
                    'columnIndex' => 6,
                    'valueType' => 'text',
                    'dataIndex' => 'employeeemployeeNumber',
                ],
                [
                    'key' => 'firstName',
                    'isDerived' => false,
                    'tableName' => 'employee',
                    'originalTableName' => 'employee',
                    'parentColumnName' => 'firstName',
                    'columnName' => 'firstName',
                    'displayName' => 'First Name',
                    'columnIndex' => 7,
                    'valueType' => 'text',
                    'dataIndex' => 'employeefirstName',
                ],
            ]);

            DB::table('reportData')->where('id', 3)->update(['selectedTables' => $selectedTables]);
        }

        $employmentHistoryReport = DB::table('reportData')->where('id', '=', 4)->first();

        if (!empty(  $employmentHistoryReport )) {
            $selectedTables = json_encode(
                [
                    [
                      'key' => 'employeeNumber',
                      'isDerived' => false,
                      'tableName' => 'employee',
                      'originalTableName' => 'employee',
                      'parentColumnName' => 'employeeNumber',
                      'columnName' => 'employeeNumber',
                      'displayName' => 'Employee Number',
                      'columnIndex' => 0,
                      'valueType' => 'text',
                      'dataIndex' => 'employeeemployeeNumber',
                    ],
                    [
                      'key' => 'firstName',
                      'isDerived' => false,
                      'tableName' => 'employee',
                      'originalTableName' => 'employee',
                      'parentColumnName' => 'firstName',
                      'columnName' => 'firstName',
                      'displayName' => 'First Name',
                      'columnIndex' => 1,
                      'valueType' => 'text',
                      'dataIndex' => 'employeefirstName',
                    ],
                     [
                      'key' => 'lastName',
                      'isDerived' => false,
                      'tableName' => 'employee',
                      'originalTableName' => 'employee',
                      'parentColumnName' => 'lastName',
                      'columnName' => 'lastName',
                      'displayName' => 'Last Name',
                      'columnIndex' => 2,
                      'valueType' => 'text',
                      'dataIndex' => 'employeelastName',
                    ],
                    [
                      'key' => 'hireDate',
                      'isDerived' => false,
                      'tableName' => 'employee',
                      'originalTableName' => 'employee',
                      'parentColumnName' => 'hireDate',
                      'columnName' => 'hireDate',
                      'displayName' => 'Hire Date',
                      'columnIndex' => 3,
                      'valueType' => 'text',
                      'dataIndex' => 'employeehireDate',
                    ],
                     [
                      'key' => 'employments.effectiveDate',
                      'isDerived' => false,
                      'tableName' => 'employeeEmployment',
                      'originalTableName' => 'employeeEmployment',
                      'parentColumnName' => 'effectiveDate',
                      'columnName' => 'effectiveDate',
                      'displayName' => 'Employments Effective Date',
                      'columnIndex' => 4,
                      'valueType' => 'text',
                      'dataIndex' => 'employeeEmploymenteffectiveDate',
                    ],
                    [
                      'key' => 'employments.employmentStatus',
                      'isDerived' => false,
                      'tableName' => 'employmentsemploymentStatus',
                      'originalTableName' => 'employeeEmployment',
                      'parentColumnName' => 'employmentStatus',
                      'columnName' => 'name',
                      'displayName' => 'Employments Employment Status',
                      'columnIndex' => 5,
                      'valueType' => 'text',
                      'dataIndex' => 'employmentsemploymentStatusname',
                    ],
                    [
                      'key' => 'employments.comment',
                      'isDerived' => false,
                      'tableName' => 'employeeEmployment',
                      'originalTableName' => 'employeeEmployment',
                      'parentColumnName' => 'comment',
                      'columnName' => 'comment',
                      'displayName' => 'Employments Comment',
                      'columnIndex' => 6,
                      'valueType' => 'text',
                      'dataIndex' => 'employeeEmploymentcomment',
                    ],
                ]
            );
            $joinCriterias =json_encode([
                [
                    'tableOneName' => 'employee',
                    'tableOneAlias' => 'employee',
                    'tableOneOperandOne' => 'id',
                    'tableOneOperandTwo' => 'currentEmploymentsId',
                    'operator' => '=',
                    'tableTwoName' => 'employeeEmployment',
                    'tableTwoAlias' => 'employeeEmployment',
                    'tableTwoOperandOne' => 'employeeId',
                    'tableTwoOperandTwo' => 'id',
                  ],
                   [
                    'tableOneName' => 'employeeEmployment',
                    'tableOneAlias' => 'employeeEmployment',
                    'tableOneOperandOne' => 'employmentStatusId',
                    'operator' => '=',
                    'tableTwoName' => 'employmentStatus',
                    'tableTwoAlias' => 'employmentsemploymentStatus',
                    'tableTwoOperandOne' => 'id',
                  ],

            ]);  
            DB::table('reportData')->where('id', 4)->update(['selectedTables' => $selectedTables,'joinCriterias' =>  $joinCriterias]);
        }
    }
}
