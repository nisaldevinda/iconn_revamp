<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumnTypesInEmployeeSalaryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employeeSalary', function (Blueprint $table) {
            if (Schema::hasColumn('employeeSalary', 'basic')) {
                $table->binary('basic')->nullable()->change();
            }

            if (Schema::hasColumn('employeeSalary', 'allowance')) {
                $table->binary('allowance')->nullable()->change();
            }

            if (Schema::hasColumn('employeeSalary', 'epfEmployer')) {
                $table->binary('epfEmployer')->nullable()->change();
            }

            if (Schema::hasColumn('employeeSalary', 'epfEmployee')) {
                $table->binary('epfEmployee')->nullable()->change();
            }

            if (Schema::hasColumn('employeeSalary', 'etf')) {
                $table->binary('etf')->nullable()->change();
            }

            if (Schema::hasColumn('employeeSalary', 'payeeTax')) {
                $table->binary('payeeTax')->nullable()->change();
            }

            if (Schema::hasColumn('employeeSalary', 'ctc')) {
                $table->binary('ctc')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employeeSalary', function (Blueprint $table) {
            if (Schema::hasColumn('employeeSalary', 'basic')) {
                $table->decimal('basic', 10, 2)->change();
            }

            if (Schema::hasColumn('employeeSalary', 'allowance')) {
                $table->decimal('allowance', 10, 2)->change();
            }

            if (Schema::hasColumn('employeeSalary', 'epfEmployer')) {
                $table->decimal('epfEmployer', 10, 2)->change();
            }

            if (Schema::hasColumn('employeeSalary', 'epfEmployee')) {
                $table->decimal('epfEmployee', 10, 2)->change();
            }

            if (Schema::hasColumn('employeeSalary', 'etf')) {
                $table->decimal('etf', 10, 2)->change();
            }

            if (Schema::hasColumn('employeeSalary', 'payeeTax')) {
                $table->decimal('payeeTax', 10, 2)->change();
            }

            if (Schema::hasColumn('employeeSalary', 'ctc')) {
                $table->decimal('ctc', 10, 2)->change();
            }
        });
    }
}
