<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeEmployeeSalaryTableFieldsOptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employeeSalary', function (Blueprint $table) {
            $table->decimal('basic', 10, 2)->nullable()->default(null)->change();
            $table->decimal('allowance', 10, 2)->nullable()->default(null)->change();
            $table->decimal('epfEmployer', 10, 2)->nullable()->default(null)->change();
            $table->decimal('epfEmployee', 10, 2)->nullable()->default(null)->change();
            $table->decimal('etf', 10, 2)->nullable()->default(null)->change();
            $table->decimal('payeeTax', 10, 2)->nullable()->default(null)->change();
            $table->decimal('ctc', 10, 2)->nullable()->default(null)->change();
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
            $table->decimal('basic', 10, 2)->change();
            $table->decimal('allowance', 10, 2)->change();
            $table->decimal('epfEmployer', 10, 2)->change();
            $table->decimal('epfEmployee', 10, 2)->change();
            $table->decimal('etf', 10, 2)->change();
            $table->decimal('payeeTax', 10, 2)->change();
            $table->decimal('ctc', 10, 2)->change();
        });
    }
}
