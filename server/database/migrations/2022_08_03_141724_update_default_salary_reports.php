<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateDefaultSalaryReports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $salaryHistoryReport = DB::table('reportData')->where('id', '=', 7)->first();

        if (!empty($salaryHistoryReport)) {
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
                    'isEncripted' => true
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
                    'isEncripted' => true
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
                    'isEncripted' => true
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
                    'isEncripted' => true
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
                    'isEncripted' => true
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
                    'isEncripted' => true
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
                    'isEncripted' => true
                ],
            ]);

            DB::table('reportData')->where('id', 7)->update(['selectedTables' => $selectedTables]);
        }


        $jobSalaryReport = DB::table('reportData')->where('id', '=', 6)->first();

        if (!empty($jobSalaryReport)) {
            $selectedTables = json_encode([
                [
                    'key' => 'jobs.jobTitle',
                    'isDerived' => false,
                    'tableName' => 'jobsjobTitle',
                    'originalTableName' => 'employeeJob',
                    'parentColumnName' => 'jobTitle',
                    'columnName' => 'name',
                    'displayName' => 'Jobs Job Title',
                    'columnIndex' => 0,
                    'valueType' => 'text',
                    'dataIndex' => 'jobsjobTitlename',
                ],
                [
                    'key' => 'salaries.basic',
                    'isDerived' => false,
                    'tableName' => 'employeeSalary',
                    'originalTableName' => 'employeeSalary',
                    'parentColumnName' => 'basic',
                    'columnName' => 'basic',
                    'displayName' => 'Salaries Basic',
                    'columnIndex' => 1,
                    'valueType' => 'text',
                    'dataIndex' => 'employeeSalarybasic',
                    'isEncripted' => true
                ],
            ]);

            DB::table('reportData')->where('id', 6)->update(['selectedTables' => $selectedTables]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $salaryHistoryReport = DB::table('reportData')->where('id', '=', 7)->first();

        if (!empty($salaryHistoryReport)) {
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
                    'dataIndex' => 'employeeSalarybasic'
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
                    'dataIndex' => 'employeeSalaryallowance'
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
                    'dataIndex' => 'employeeSalaryepfEmployer'
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
                    'dataIndex' => 'employeeSalaryepfEmployee'
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
                    'dataIndex' => 'employeeSalaryetf'
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
                    'dataIndex' => 'employeeSalarypayeeTax'
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
                    'dataIndex' => 'employeeSalaryctc'
                ],
            ]);

            DB::table('reportData')->where('id', 7)->update(['selectedTables' => $selectedTables]);
        }


        $jobSalaryReport = DB::table('reportData')->where('id', '=', 6)->first();

        if (!empty($jobSalaryReport)) {
            $selectedTables = json_encode([
                [
                    'key' => 'jobs.jobTitle',
                    'isDerived' => false,
                    'tableName' => 'jobsjobTitle',
                    'originalTableName' => 'employeeJob',
                    'parentColumnName' => 'jobTitle',
                    'columnName' => 'name',
                    'displayName' => 'Jobs Job Title',
                    'columnIndex' => 0,
                    'valueType' => 'text',
                    'dataIndex' => 'jobsjobTitlename',
                ],
                [
                    'key' => 'salaries.basic',
                    'isDerived' => false,
                    'tableName' => 'employeeSalary',
                    'originalTableName' => 'employeeSalary',
                    'parentColumnName' => 'basic',
                    'columnName' => 'basic',
                    'displayName' => 'Salaries Basic',
                    'columnIndex' => 1,
                    'valueType' => 'text',
                    'dataIndex' => 'employeeSalarybasic'
                ],
            ]);

            DB::table('reportData')->where('id', 6)->update(['selectedTables' => $selectedTables]);
        }
    }
}
