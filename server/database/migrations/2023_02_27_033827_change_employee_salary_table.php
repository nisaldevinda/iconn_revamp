<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeEmployeeSalaryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employeeSalary', function (Blueprint $table) {
            $table->dropColumn('basic');
            $table->dropColumn('allowance');
            $table->dropColumn('epfEmployer');
            $table->dropColumn('epfEmployee');
            $table->dropColumn('etf');
            $table->dropColumn('payeeTax');
            $table->dropColumn('ctc');
            $table->json('salaryDetails')->default('{}');
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
            $table->decimal('basic', 10, 2);
            $table->decimal('allowance', 10, 2);
            $table->decimal('epfEmployer', 10, 2);
            $table->decimal('epfEmployee', 10, 2);
            $table->decimal('etf', 10, 2);
            $table->decimal('payeeTax', 10, 2);
            $table->decimal('ctc', 10, 2);
            $table->dropColumn('salaryDetails');
        });
    }
}
