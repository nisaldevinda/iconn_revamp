<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeSalaryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employeeSalary', function (Blueprint $table) {
            $table->id();
            $table->integer('employeeId');
            $table->date('effectiveDate')->nullable()->default(null);
            $table->decimal('basic', 10, 2);
            $table->decimal('allowance', 10, 2);
            $table->decimal('epfEmployer', 10, 2);
            $table->decimal('epfEmployee', 10, 2);
            $table->decimal('etf', 10, 2);
            $table->decimal('payeeTax', 10, 2);
            $table->decimal('ctc', 10, 2);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employeeSalary');
    }
}
