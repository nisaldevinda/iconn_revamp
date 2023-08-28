<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDefaultSystemReportsToReportDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $reportData =  array(
            [
                "reportName"=> "Employee Termination Report",
                "outputMethod"=> "pdf",
                "pageSize"=> 20,
                "targetKeys"=>json_encode( [
                    "employeeNumber",
                    "firstName",
                    "middleName",
                    "lastName",
                    "jobs.department",
                    "jobs.jobTitle",
                    "employments.effectiveDate",
                    "employments.terminationType",
                ]),
                "selectedTables"=>json_encode([

                    [
                        "key"=> "employeeNumber",
                        "isDerived"=> false,
                        "tableName"=> "employee",
                        "originalTableName"=> "employee",
                        "parentColumnName"=> "employeeNumber",
                        "columnName"=> "employeeNumber",
                        "displayName"=> "Employee Number",
                        "columnIndex"=> 0,
                        "valueType"=> "employeeNumber",
                        "dataIndex"=> "employeeemployeeNumber"  
                    ],
                    [
                        "key"=> "firstName",
                        "isDerived"=> false,
                        "tableName"=> "employee",
                        "originalTableName"=> "employee",
                        "parentColumnName"=> "firstName",
                        "columnName"=> "firstName",
                        "displayName"=> "First Name",
                        "columnIndex"=> 1,
                        "valueType"=> "string",
                        "dataIndex"=> "employeefirstName"  
                    ],
                    [
                        "key"=> "middleName",
                        "isDerived"=> false,
                        "tableName"=> "employee",
                        "originalTableName"=> "employee",
                        "parentColumnName"=> "middleName",
                        "columnName"=> "middleName",
                        "displayName"=> "Middle Name",
                        "columnIndex"=> 2,
                        "valueType"=> "string",
                        "dataIndex"=> "employeemiddleName"  
                    ],
                    [
                        "key"=> "lastName",
                        "isDerived"=> false,
                        "tableName"=> "employee",
                        "originalTableName"=> "employee",
                        "parentColumnName"=> "lastName",
                        "columnName"=> "lastName",
                        "displayName"=> "Last Name",
                        "columnIndex"=> 3,
                        "valueType"=> "string",
                        "dataIndex"=> "employeelastName"  
                    ],
                    [
                        "key" => "jobs.department",
                        "isDerived"=> false,
                        "tableName"=> "jobsdepartment",
                        "originalTableName"=> "employeeJob",
                        "parentColumnName"=> "department",
                        "columnName"=> "name",
                        "displayName"=> "Jobs - Department",
                        "columnIndex"=> 4,
                        "valueType"=> "model",
                        "dataIndex"=> "jobsdepartmentname",
                        "showHistory" => false,  
                        
                    ],
                    [
                        "key" => "jobs.jobTitle",
                        "isDerived"=> false,
                        "tableName"=> "jobsjobTitle",
                        "originalTableName"=> "employeeJob",
                        "parentColumnName"=> "jobTitle",
                        "columnName"=> "name",
                        "displayName"=> "Jobs - jobTitle",
                        "columnIndex"=> 5,
                        "valueType"=> "model",
                        "dataIndex"=> "jobsjobTitlename",
                        "showHistory" => false,  
                        
                    ],
                    [
                        "key" => "employments.effectiveDate",
                        "isDerived"=> false,
                        "tableName"=> "employeeEmployment",
                        "originalTableName"=> "employeeEmployment",
                        "parentColumnName"=> "effectiveDate",
                        "columnName"=> "effectiveDate",
                        "displayName"=> "Employments - Effective Date",
                        "columnIndex"=> 7,
                        "valueType"=> "timestamp",
                        "dataIndex"=> "employeeEmploymenteffectiveDate",
                        "showHistory" => false,  
                        
                    ],
                    [
                        "key" => "employments.terminationType",
                        "isDerived"=> false,
                        "tableName"=> "employmentsterminationType",
                        "originalTableName"=> "employeeEmployment",
                        "parentColumnName"=> "terminationType",
                        "columnName"=> "name",
                        "displayName"=> "Employments - Termination Type",
                        "columnIndex"=> 6,
                        "valueType"=> "model",
                        "dataIndex"=> "employmentsterminationTypename",
                        "showHistory" => false,  
                        
                    ]
                   
                ]),
                "joinCriterias"=>json_encode([
                    [
                        "tableOneName"=> "employee",
                        "tableOneAlias"=> "employee",
                        "tableOneOperandOne"=> "id",
                        "tableOneOperandTwo"=> "currentJobsId",
                        "operator"=> "=",
                        "tableTwoName"=> "employeeJob",
                        "tableTwoAlias"=> "employeeJob",
                        "tableTwoOperandOne"=> "employeeId",
                        "tableTwoOperandTwo"=>"id"
            
                    ],
                    [
                        "tableOneName"=> "employeeJob",
                        "tableOneAlias"=> "employeeJob",
                        "tableOneOperandOne"=> "departmentId",
                        "operator"=> "=",
                        "tableTwoName"=> "department",
                        "tableTwoAlias"=> "jobsdepartment",
                        "tableTwoOperandOne"=> "id",
                        
                    ],
                    [
                        "tableOneName"=> "employeeJob",
                        "tableOneAlias"=> "employeeJob",
                        "tableOneOperandOne"=> "jobTitleId",
                        "operator"=> "=",
                        "tableTwoName"=> "jobTitle",
                        "tableTwoAlias"=> "jobsjobTitle",
                        "tableTwoOperandOne"=> "id",
                        
                    ],
                    [
                        "tableOneName"=> "employee",
                        "tableOneAlias"=> "employee",
                        "tableOneOperandOne"=> "id",
                        "tableOneOperandTwo" => "currentEmploymentsId",
                        "operator" => "=",
                        "tableTwoName" => "employeeEmployment",
                        "tableTwoAlias" => "employeeEmployment",
                        "tableTwoOperandOne" => "employeeId",
                        "tableTwoOperandTwo" => "id",
                    ],
                    [
                        "tableOneName" => "employeeEmployment",
                        "tableOneAlias" => "employeeEmployment",
                        "tableOneOperandOne" => "terminationTypeId",
                        "operator" => "=",
                        "tableTwoName" => "terminationType",
                        "tableTwoAlias" => "employmentsterminationType",
                        "tableTwoOperandOne" => "id",
                        
                    ],
                ]),
                "hideDetailedData"=>false,
                "isSystemReport"=>true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                "isChartAvailable"=> false,
                "chartType"=> "pieChart",
                "aggregateType"=> "null",
                "aggregateField"=> "null",
                "showSummeryTable"=> true,
                "groupBy"=> json_encode([]),
            ],
            [
                "reportName"=> "Employment Status",
                "outputMethod"=> "pdf",
                "pageSize"=> 20,
                "targetKeys"=>json_encode( [
                    "employeeNumber",
                    "firstName",
                    "middleName",
                    "lastName",
                    "employments.employmentStatus",
                ]),
                "selectedTables"=>json_encode([

                    [
                        "key"=> "employeeNumber",
                        "isDerived"=> false,
                        "tableName"=> "employee",
                        "originalTableName"=> "employee",
                        "parentColumnName"=> "employeeNumber",
                        "columnName"=> "employeeNumber",
                        "displayName"=> "Employee Number",
                        "columnIndex"=> 0,
                        "valueType"=> "employeeNumber",
                        "dataIndex"=> "employeeemployeeNumber"  
                    ],
                    [
                        "key"=> "firstName",
                        "isDerived"=> false,
                        "tableName"=> "employee",
                        "originalTableName"=> "employee",
                        "parentColumnName"=> "firstName",
                        "columnName"=> "firstName",
                        "displayName"=> "First Name",
                        "columnIndex"=> 1,
                        "valueType"=> "string",
                        "dataIndex"=> "employeefirstName"  
                    ],
                    [
                        "key"=> "middleName",
                        "isDerived"=> false,
                        "tableName"=> "employee",
                        "originalTableName"=> "employee",
                        "parentColumnName"=> "middleName",
                        "columnName"=> "middleName",
                        "displayName"=> "Middle Name",
                        "columnIndex"=> 2,
                        "valueType"=> "string",
                        "dataIndex"=> "employeemiddleName"  
                    ],
                    [
                        "key"=> "lastName",
                        "isDerived"=> false,
                        "tableName"=> "employee",
                        "originalTableName"=> "employee",
                        "parentColumnName"=> "lastName",
                        "columnName"=> "lastName",
                        "displayName"=> "Last Name",
                        "columnIndex"=> 3,
                        "valueType"=> "string",
                        "dataIndex"=> "employeelastName"  
                    ],
                    [
                        "key" => "employments.employmentStatus",
                        "isDerived"=> false,
                        "tableName"=> "employmentsemploymentStatus",
                        "originalTableName"=> "employeeEmployment",
                        "parentColumnName"=> "employmentStatus",
                        "columnName"=> "name",
                        "displayName"=> "Employments - Employment Status",
                        "columnIndex"=> 4,
                        "valueType"=> "model",
                        "dataIndex"=> "employmentsemploymentStatusname",
                        "showHistory" => false,  
                        
                    ]
                    
                ]),
                "joinCriterias"=>json_encode([
                    [
                        "tableOneName"=> "employee",
                        "tableOneAlias"=> "employee",
                        "tableOneOperandOne"=> "id",
                        "tableOneOperandTwo"=> "currentEmploymentsId",
                        "operator"=> "=",
                        "tableTwoName"=> "employeeEmployment",
                        "tableTwoAlias"=> "employeeEmployment",
                        "tableTwoOperandOne"=> "employeeId",
                        "tableTwoOperandTwo"=>"id"
            
                    ],
                    [
                        "tableOneName"=> "employeeEmployment",
                        "tableOneAlias"=> "employeeEmployment",
                        "tableOneOperandOne"=> "employmentStatusId",
                        "operator"=> "=",
                        "tableTwoName"=> "employmentStatus",
                        "tableTwoAlias"=> "employmentsemploymentStatus",
                        "tableTwoOperandOne"=> "id",
                        
                    ]
                ]),
                "hideDetailedData"=>false,
                "isSystemReport"=>true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                "isChartAvailable"=> false,
                "chartType"=> "pieChart",
                "aggregateType"=> "null",
                "aggregateField"=> "null",
                "showSummeryTable"=> true,
                "groupBy"=> json_encode([]),
            ],
            [
                "reportName"=> "New Hire",
                "outputMethod"=> "pdf",
                "pageSize"=> 20,
                "targetKeys"=>json_encode( [
                    "employeeNumber",
                    "firstName",
                    "middleName",
                    "lastName",
                    "hireDate",
                    "jobs.department",
                    "jobs.location",
                    "employments.employmentStatus",
                ]),
                "selectedTables"=>json_encode([

                    [
                        "key"=> "employeeNumber",
                        "isDerived"=> false,
                        "tableName"=> "employee",
                        "originalTableName"=> "employee",
                        "parentColumnName"=> "employeeNumber",
                        "columnName"=> "employeeNumber",
                        "displayName"=> "Employee Number",
                        "columnIndex"=> 0,
                        "valueType"=> "employeeNumber",
                        "dataIndex"=> "employeeemployeeNumber"  
                    ],
                    [
                        "key"=> "firstName",
                        "isDerived"=> false,
                        "tableName"=> "employee",
                        "originalTableName"=> "employee",
                        "parentColumnName"=> "firstName",
                        "columnName"=> "firstName",
                        "displayName"=> "First Name",
                        "columnIndex"=> 1,
                        "valueType"=> "string",
                        "dataIndex"=> "employeefirstName"  
                    ],
                    [
                        "key"=> "middleName",
                        "isDerived"=> false,
                        "tableName"=> "employee",
                        "originalTableName"=> "employee",
                        "parentColumnName"=> "middleName",
                        "columnName"=> "middleName",
                        "displayName"=> "Middle Name",
                        "columnIndex"=> 2,
                        "valueType"=> "string",
                        "dataIndex"=> "employeemiddleName"  
                    ],
                    [
                        "key"=> "lastName",
                        "isDerived"=> false,
                        "tableName"=> "employee",
                        "originalTableName"=> "employee",
                        "parentColumnName"=> "lastName",
                        "columnName"=> "lastName",
                        "displayName"=> "Last Name",
                        "columnIndex"=> 3,
                        "valueType"=> "string",
                        "dataIndex"=> "employeelastName"  
                    ],
                    [
                        "key" => "hireDate",
                        "isDerived" => false,
                        "tableName" => "employee",
                        "originalTableName" => "employee",
                        "parentColumnName" => "hireDate",
                        "columnName" => "hireDate",
                        "displayName" => "Hire Date",
                        "columnIndex" => 4,
                        "valueType" => "timestamp",
                        "dataIndex" => "employeehireDate",
                    ],
                    [
                        "key" => "jobs.department",
                        "isDerived" => false,
                        "tableName" => "jobsdepartment",
                        "originalTableName" => "employeeJob",
                        "parentColumnName" => "department",
                        "columnName" => "name",
                        "displayName" => "Jobs - Department",
                        "columnIndex" => 5,
                        "valueType" => "model",
                        "dataIndex" => "jobsdepartmentname",
                        "showHistory" => false,
                    ],
                    [
                        "key" => "jobs.location",
                        "isDerived" => false,
                        "tableName" => "jobslocation",
                        "originalTableName" => "employeeJob",
                        "parentColumnName" => "location",
                        "columnName" => "name",
                        "displayName" => "Jobs - Location",
                        "columnIndex" => 6,
                        "valueType" => "model",
                        "dataIndex" => "jobslocationname",
                        "showHistory" => false,
                    ],
                    [
                        "key" => "employments.employmentStatus",
                        "isDerived"=> false,
                        "tableName"=> "employmentsemploymentStatus",
                        "originalTableName"=> "employeeEmployment",
                        "parentColumnName"=> "employmentStatus",
                        "columnName"=> "name",
                        "displayName"=> "Employments - Employment Status",
                        "columnIndex"=> 7,
                        "valueType"=> "model",
                        "dataIndex"=> "employmentsemploymentStatusname",
                        "showHistory" => false,  
                        
                    ]
                    
                ]),
                "joinCriterias"=>json_encode([
                    [
                        "tableOneName"=> "employee",
                        "tableOneAlias"=> "employee",
                        "tableOneOperandOne"=> "id",
                        "tableOneOperandTwo"=> "currentJobsId",
                        "operator"=> "=",
                        "tableTwoName"=> "employeeJob",
                        "tableTwoAlias"=> "employeeJob",
                        "tableTwoOperandOne"=> "employeeId",
                        "tableTwoOperandTwo"=>"id"
                    ],
                    [
                        "tableOneName" => "employeeJob",
                        "tableOneAlias" => "employeeJob",
                        "tableOneOperandOne" => "departmentId",
                        "operator" => "=",
                        "tableTwoName" => "department",
                        "tableTwoAlias" => "jobsdepartment",
                        "tableTwoOperandOne" => "id",
                    ],
                    [
                        "tableOneName" => "employeeJob",
                        "tableOneAlias" => "employeeJob",
                        "tableOneOperandOne" => "locationId",
                        "operator" => "=",
                        "tableTwoName" => "location",
                        "tableTwoAlias" => "jobslocation",
                        "tableTwoOperandOne" => "id",
                    ],
                    [
                        "tableOneName" => "employee",
                        "tableOneAlias" => "employee",
                        "tableOneOperandOne" => "id",
                        "tableOneOperandTwo" => "currentEmploymentsId",
                        "operator" => "=",
                        "tableTwoName" => "employeeEmployment",
                        "tableTwoAlias" => "employeeEmployment",
                        "tableTwoOperandOne" => "employeeId",
                        "tableTwoOperandTwo" => "id",
                    ],
                    [
                        "tableOneName"=> "employeeEmployment",
                        "tableOneAlias"=> "employeeEmployment",
                        "tableOneOperandOne"=> "employmentStatusId",
                        "operator"=> "=",
                        "tableTwoName"=> "employmentStatus",
                        "tableTwoAlias"=> "employmentsemploymentStatus",
                        "tableTwoOperandOne"=> "id",
                        
                    ]
                ]),
                "hideDetailedData"=>false,
                "isSystemReport"=>true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                "isChartAvailable"=> false,
                "chartType"=> "pieChart",
                "aggregateType"=> "null",
                "aggregateField"=> "null",
                "showSummeryTable"=> true,
                "groupBy"=> json_encode([]),
            ],
            [
                "reportName"=> "New Hire summary",
                "outputMethod"=> "pdf",
                "pageSize"=> 20,
                "targetKeys"=>json_encode( [
                    "employeeNumber",
                    "firstName",
                    "middleName",
                    "lastName",
                    "hireDate",
                    "jobs.department",
                    "jobs.location",
                    "employments.employmentStatus",
                ]),
                "selectedTables"=>json_encode([

                    [
                        "key"=> "employeeNumber",
                        "isDerived"=> false,
                        "tableName"=> "employee",
                        "originalTableName"=> "employee",
                        "parentColumnName"=> "employeeNumber",
                        "columnName"=> "employeeNumber",
                        "displayName"=> "Employee Number",
                        "columnIndex"=> 0,
                        "valueType"=> "employeeNumber",
                        "dataIndex"=> "employeeemployeeNumber"  
                    ],
                    [
                        "key"=> "firstName",
                        "isDerived"=> false,
                        "tableName"=> "employee",
                        "originalTableName"=> "employee",
                        "parentColumnName"=> "firstName",
                        "columnName"=> "firstName",
                        "displayName"=> "First Name",
                        "columnIndex"=> 1,
                        "valueType"=> "string",
                        "dataIndex"=> "employeefirstName"  
                    ],
                    [
                        "key"=> "middleName",
                        "isDerived"=> false,
                        "tableName"=> "employee",
                        "originalTableName"=> "employee",
                        "parentColumnName"=> "middleName",
                        "columnName"=> "middleName",
                        "displayName"=> "Middle Name",
                        "columnIndex"=> 2,
                        "valueType"=> "string",
                        "dataIndex"=> "employeemiddleName"  
                    ],
                    [
                        "key"=> "lastName",
                        "isDerived"=> false,
                        "tableName"=> "employee",
                        "originalTableName"=> "employee",
                        "parentColumnName"=> "lastName",
                        "columnName"=> "lastName",
                        "displayName"=> "Last Name",
                        "columnIndex"=> 3,
                        "valueType"=> "string",
                        "dataIndex"=> "employeelastName"  
                    ],
                    [
                        "key" => "hireDate",
                        "isDerived" => false,
                        "tableName" => "employee",
                        "originalTableName" => "employee",
                        "parentColumnName" => "hireDate",
                        "columnName" => "hireDate",
                        "displayName" => "Hire Date",
                        "columnIndex" => 4,
                        "valueType" => "timestamp",
                        "dataIndex" => "employeehireDate",
                    ],
                    [
                        "key" => "jobs.department",
                        "isDerived" => false,
                        "tableName" => "jobsdepartment",
                        "originalTableName" => "employeeJob",
                        "parentColumnName" => "department",
                        "columnName" => "name",
                        "displayName" => "Jobs - Department",
                        "columnIndex" => 5,
                        "valueType" => "model",
                        "dataIndex" => "jobsdepartmentname",
                        "showHistory" => false,
                    ],
                    [
                        "key" => "jobs.location",
                        "isDerived" => false,
                        "tableName" => "jobslocation",
                        "originalTableName" => "employeeJob",
                        "parentColumnName" => "location",
                        "columnName" => "name",
                        "displayName" => "Jobs - Location",
                        "columnIndex" => 6,
                        "valueType" => "model",
                        "dataIndex" => "jobslocationname",
                        "showHistory" => false,
                    ],
                    [
                        "key" => "employments.employmentStatus",
                        "isDerived"=> false,
                        "tableName"=> "employmentsemploymentStatus",
                        "originalTableName"=> "employeeEmployment",
                        "parentColumnName"=> "employmentStatus",
                        "columnName"=> "name",
                        "displayName"=> "Employments - Employment Status",
                        "columnIndex"=> 7,
                        "valueType"=> "model",
                        "dataIndex"=> "employmentsemploymentStatusname",
                        "showHistory" => false,  
                        
                    ]
                    
                ]),
                "joinCriterias"=>json_encode([
                    [
                        "tableOneName"=> "employee",
                        "tableOneAlias"=> "employee",
                        "tableOneOperandOne"=> "id",
                        "tableOneOperandTwo"=> "currentJobsId",
                        "operator"=> "=",
                        "tableTwoName"=> "employeeJob",
                        "tableTwoAlias"=> "employeeJob",
                        "tableTwoOperandOne"=> "employeeId",
                        "tableTwoOperandTwo"=>"id"
                    ],
                    [
                        "tableOneName" => "employeeJob",
                        "tableOneAlias" => "employeeJob",
                        "tableOneOperandOne" => "departmentId",
                        "operator" => "=",
                        "tableTwoName" => "department",
                        "tableTwoAlias" => "jobsdepartment",
                        "tableTwoOperandOne" => "id",
                    ],
                    [
                        "tableOneName" => "employeeJob",
                        "tableOneAlias" => "employeeJob",
                        "tableOneOperandOne" => "locationId",
                        "operator" => "=",
                        "tableTwoName" => "location",
                        "tableTwoAlias" => "jobslocation",
                        "tableTwoOperandOne" => "id",
                    ],
                    [
                        "tableOneName" => "employee",
                        "tableOneAlias" => "employee",
                        "tableOneOperandOne" => "id",
                        "tableOneOperandTwo" => "currentEmploymentsId",
                        "operator" => "=",
                        "tableTwoName" => "employeeEmployment",
                        "tableTwoAlias" => "employeeEmployment",
                        "tableTwoOperandOne" => "employeeId",
                        "tableTwoOperandTwo" => "id",
                    ],
                    [
                        "tableOneName"=> "employeeEmployment",
                        "tableOneAlias"=> "employeeEmployment",
                        "tableOneOperandOne"=> "employmentStatusId",
                        "operator"=> "=",
                        "tableTwoName"=> "employmentStatus",
                        "tableTwoAlias"=> "employmentsemploymentStatus",
                        "tableTwoOperandOne"=> "id",
                        
                    ]
                ]),
                "hideDetailedData"=>false,
                "isSystemReport"=>true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                "isChartAvailable"=> true,
                "chartType"=> "pieChart",
                "aggregateType"=> "count",
                "aggregateField"=> "employeehireDate",
                "showSummeryTable"=> true,
                "groupBy"=> json_encode([
                    'employeehireDate'
                ]),
            ],
            [
                "reportName"=> "Employee Service Period",
                "outputMethod"=> "pdf",
                "pageSize"=> 20,
                "targetKeys"=>json_encode( [
                    "employeeNumber",
                    "firstName",
                    "middleName",
                    "lastName",
                    "hireDate",
                    "jobs.department",
                    "payGrade"
                ]),
                "selectedTables"=>json_encode([

                    [
                        "key"=> "employeeNumber",
                        "isDerived"=> false,
                        "tableName"=> "employee",
                        "originalTableName"=> "employee",
                        "parentColumnName"=> "employeeNumber",
                        "columnName"=> "employeeNumber",
                        "displayName"=> "Employee Number",
                        "columnIndex"=> 0,
                        "valueType"=> "employeeNumber",
                        "dataIndex"=> "employeeemployeeNumber"  
                    ],
                    [
                        "key"=> "firstName",
                        "isDerived"=> false,
                        "tableName"=> "employee",
                        "originalTableName"=> "employee",
                        "parentColumnName"=> "firstName",
                        "columnName"=> "firstName",
                        "displayName"=> "First Name",
                        "columnIndex"=> 1,
                        "valueType"=> "string",
                        "dataIndex"=> "employeefirstName"  
                    ],
                    [
                        "key"=> "middleName",
                        "isDerived"=> false,
                        "tableName"=> "employee",
                        "originalTableName"=> "employee",
                        "parentColumnName"=> "middleName",
                        "columnName"=> "middleName",
                        "displayName"=> "Middle Name",
                        "columnIndex"=> 2,
                        "valueType"=> "string",
                        "dataIndex"=> "employeemiddleName"  
                    ],
                    [
                        "key"=> "lastName",
                        "isDerived"=> false,
                        "tableName"=> "employee",
                        "originalTableName"=> "employee",
                        "parentColumnName"=> "lastName",
                        "columnName"=> "lastName",
                        "displayName"=> "Last Name",
                        "columnIndex"=> 3,
                        "valueType"=> "string",
                        "dataIndex"=> "employeelastName"  
                    ],
                    [
                        "key" => "hireDate",
                        "isDerived" => false,
                        "tableName" => "employee",
                        "originalTableName" => "employee",
                        "parentColumnName" => "hireDate",
                        "columnName" => "hireDate",
                        "displayName" => "Hire Date",
                        "columnIndex" => 4,
                        "valueType" => "timestamp",
                        "dataIndex" => "employeehireDate",
                    ],
                    [
                        "key" => "jobs.department",
                        "isDerived" => false,
                        "tableName" => "jobsdepartment",
                        "originalTableName" => "employeeJob",
                        "parentColumnName" => "department",
                        "columnName" => "name",
                        "displayName" => "Jobs - Department",
                        "columnIndex" => 5,
                        "valueType" => "model",
                        "dataIndex" => "jobsdepartmentname",
                        "showHistory" => false,
                    ],
                    [
                        "key" => "payGrade",
                        "isDerived" => false,
                        "originalTableName" => "employee",
                        "tableName" => "compensationpayGrade",
                        "parentColumnName" => "payGradesId",
                        "columnName" => "name",
                        "displayName" => "Pay Grade",
                        "columnIndex" => 6,
                        "valueType" => "model",
                        "dataIndex" => "compensationpayGradename",
                    ]
                    
                ]),
                "joinCriterias"=>json_encode([
                    [
                        "tableOneName"=> "employee",
                        "tableOneAlias"=> "employee",
                        "tableOneOperandOne"=> "id",
                        "tableOneOperandTwo"=> "currentJobsId",
                        "operator"=> "=",
                        "tableTwoName"=> "employeeJob",
                        "tableTwoAlias"=> "employeeJob",
                        "tableTwoOperandOne"=> "employeeId",
                        "tableTwoOperandTwo"=>"id"
                    ],
                    [
                        "tableOneName" => "employeeJob",
                        "tableOneAlias" => "employeeJob",
                        "tableOneOperandOne" => "departmentId",
                        "operator" => "=",
                        "tableTwoName" => "department",
                        "tableTwoAlias" => "jobsdepartment",
                        "tableTwoOperandOne" => "id",
                    ],
                    [
                        "tableOneName" => "employee",
                        "tableOneAlias" => "employee",
                        "tableOneOperandOne" => "payGradeId",
                        "operator" => "=",
                        "tableTwoName" => "payGrades",
                        "tableTwoOperandOne" => "id",
                        "tableTwoAlias" => "compensationpayGrade",
                    ]
                ]),
                "hideDetailedData"=>false,
                "isSystemReport"=>true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                "isChartAvailable"=> false,
                "chartType"=> "pieChart",
                "aggregateType"=> "null",
                "aggregateField"=> "null",
                "showSummeryTable"=> true,
                "groupBy"=> json_encode([]),
            ]
        );

        try {
            $reportName = ['Employee Termination Report','Employment Status','New Hire','New Hire summary','Employee Service Period'];
            $record = DB::table('reportData')->whereIn('reportName', $reportName)->first();
            if ($record) {
                return ('report exists');
            }

            DB::table('reportData')->insert($reportData);
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
            $reportName = ['Employee Termination Report','Employment Status','New Hire','New Hire summary','Employee Service Period'];
            $record = DB::table('reportData')->whereIn('reportName',  $reportName)->first();

            if (empty($record)) {
                return ('report does not exist');
            }

            $affectedRows = DB::table('reportData')->whereIn('reportName',  $reportName)->delete();

            return ($affectedRows) ? true : false;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
