<?php

use Illuminate\Database\Migrations\Migration;

class UpdateSalaryHistoryReports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            $salaryDetailsReportData = [
                "reportName" => "Salary History",
                "outputMethod" => "pdf",
                "pageSize" => 20,
                "targetKeys" => json_encode(["employeeNumber", "firstName", "lastName", "salaries.effectiveDate", "salaries.salaryDetails"]),
                "selectedTables" => json_encode([
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
                        'key' => 'salaries.effectiveDate',
                        'isDerived' => false,
                        'tableName' => 'employeeSalary',
                        'originalTableName' => 'employeeSalary',
                        'parentColumnName' => 'effectiveDate',
                        'columnName' => 'effectiveDate',
                        'displayName' => 'Salaries Effective Date',
                        'columnIndex' => 3,
                        'valueType' => 'text',
                        'dataIndex' => 'employeeSalaryeffectiveDate',
                    ],
                    [
                        'key' => 'salaries.salaryDetails',
                        'isDerived' => true,
                        'tableName' => 'employeeSalary',
                        'originalTableName' => 'employeeSalary',
                        'parentColumnName' => 'salaryDetails',
                        'columnName' => 'salaryDetails',
                        'displayName' => 'Salary Details',
                        'columnIndex' => 4,
                        'valueType' => 'text',
                        'dataIndex' => 'employeeSalarysalaryDetails',
                    ]
                ]),
                "joinCriterias" => json_encode([
                    [
                        'tableOneName' => 'employee',
                        'tableOneAlias' => 'employee',
                        'tableOneOperandOne' => 'id',
                        'tableOneOperandTwo' => 'currentSalariesId',
                        'operator' => '=',
                        'tableTwoName' => 'employeeSalary',
                        'tableTwoAlias' => 'employeeSalary',
                        'tableTwoOperandOne' => 'employeeId',
                        'tableTwoOperandTwo' => 'id',
                    ],
                ]),
                "hideDetailedData" => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                "isSystemReport" => true,
                "id" => 7,
                "isChartAvailable" => false,
                "chartType" => "pieChart",
                "aggregateType" => null,
                "aggregateField" => null,
                "showSummeryTable" => false,
                "groupBy" => json_encode([]),
            ];

            DB::table('reportData')
                ->where("reportName", "Salary History")
                ->update($salaryDetailsReportData);
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
            $salaryDetailsReportData = [
                "reportName" => "Salary History",
                "outputMethod" => "pdf",
                "pageSize" => 20,
                "targetKeys" => json_encode(["employeeNumber", "firstName", "lastName", "salaries.effectiveDate", "salaries.basic", "salaries.allowance", "salaries.epfEmployer", "salaries.epfEmployee", "salaries.etf", "salaries.payeeTax", "salaries.ctc"]),
                "selectedTables" => json_encode([
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
                        'key' => 'salaries.effectiveDate',
                        'isDerived' => false,
                        'tableName' => 'employeeSalary',
                        'originalTableName' => 'employeeSalary',
                        'parentColumnName' => 'effectiveDate',
                        'columnName' => 'effectiveDate',
                        'displayName' => 'Salaries Effective Date',
                        'columnIndex' => 3,
                        'valueType' => 'text',
                        'dataIndex' => 'employeeSalaryeffectiveDate',
                    ],
                    [
                        'key' => 'salaries.basic',
                        'isDerived' => false,
                        'tableName' => 'employeeSalary',
                        'originalTableName' => 'employeeSalary',
                        'parentColumnName' => 'basic',
                        'columnName' => 'basic',
                        'displayName' => 'Salaries Basic',
                        'columnIndex' => 4,
                        'valueType' => 'text',
                        'dataIndex' => 'employeeSalarybasic',
                    ],
                    [
                        'key' => 'salaries.allowance',
                        'isDerived' => false,
                        'tableName' => 'employeeSalary',
                        'originalTableName' => 'employeeSalary',
                        'parentColumnName' => 'allowance',
                        'columnName' => 'allowance',
                        'displayName' => 'Salaries Allowance',
                        'columnIndex' => 5,
                        'valueType' => 'text',
                        'dataIndex' => 'employeeSalaryallowance',
                    ],
                    [
                        'key' => 'salaries.epfEmployer',
                        'isDerived' => false,
                        'tableName' => 'employeeSalary',
                        'originalTableName' => 'employeeSalary',
                        'parentColumnName' => 'epfEmployer',
                        'columnName' => 'epfEmployer',
                        'displayName' => 'Salaries EPF – Employer',
                        'columnIndex' => 6,
                        'valueType' => 'text',
                        'dataIndex' => 'employeeSalaryepfEmployer',
                    ],
                    [
                        'key' => 'salaries.epfEmployee',
                        'isDerived' => false,
                        'tableName' => 'employeeSalary',
                        'originalTableName' => 'employeeSalary',
                        'parentColumnName' => 'epfEmployee',
                        'columnName' => 'epfEmployee',
                        'displayName' => 'Salaries EPF – Employee',
                        'columnIndex' => 7,
                        'valueType' => 'text',
                        'dataIndex' => 'employeeSalaryepfEmployee',
                    ],
                    [
                        'key' => 'salaries.etf',
                        'isDerived' => false,
                        'tableName' => 'employeeSalary',
                        'originalTableName' => 'employeeSalary',
                        'parentColumnName' => 'etf',
                        'columnName' => 'etf',
                        'displayName' => 'Salaries ETF',
                        'columnIndex' => 8,
                        'valueType' => 'text',
                        'dataIndex' => 'employeeSalaryetf',
                    ],
                    [
                        'key' => 'salaries.payeeTax',
                        'isDerived' => false,
                        'tableName' => 'employeeSalary',
                        'originalTableName' => 'employeeSalary',
                        'parentColumnName' => 'payeeTax',
                        'columnName' => 'payeeTax',
                        'displayName' => 'Salaries Payee Tax',
                        'columnIndex' => 9,
                        'valueType' => 'text',
                        'dataIndex' => 'employeeSalarypayeeTax',
                    ],
                    [
                        'key' => 'salaries.ctc',
                        'isDerived' => false,
                        'tableName' => 'employeeSalary',
                        'originalTableName' => 'employeeSalary',
                        'parentColumnName' => 'ctc',
                        'columnName' => 'ctc',
                        'displayName' => 'Salaries CTC',
                        'columnIndex' => 10,
                        'valueType' => 'text',
                        'dataIndex' => 'employeeSalaryctc',
                    ],
                ]),
                "joinCriterias" => json_encode([
                    [
                        'tableOneName' => 'employee',
                        'tableOneAlias' => 'employee',
                        'tableOneOperandOne' => 'id',
                        'tableOneOperandTwo' => 'currentSalariesId',
                        'operator' => '=',
                        'tableTwoName' => 'employeeSalary',
                        'tableTwoAlias' => 'employeeSalary',
                        'tableTwoOperandOne' => 'employeeId',
                        'tableTwoOperandTwo' => 'id',
                    ],
                ]),
                "hideDetailedData" => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                "isSystemReport" => true,
                "id" => 7,
                "isChartAvailable" => false,
                "chartType" => "pieChart",
                "aggregateType" => null,
                "aggregateField" => null,
                "showSummeryTable" => false,
                "groupBy" => json_encode([]),
            ];

            DB::table('reportData')
                ->where("reportName", "Salary History")
                ->update($salaryDetailsReportData);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
