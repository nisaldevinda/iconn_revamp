<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveAccrualTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leaveAccrual', function (Blueprint $table) {
            $table->id();
            $table->integer('leaveTypeId');
            $table->integer('leaveEmployeeGroupId');
            $table->enum('accrualFrequency', ['MONTHLY', 'ANNUAL']);
            $table->enum('accrueEvery', [1,2,3,4,6])->nullable();
            $table->string('dayOfCreditingForAnnualFrequency')->nullable();
            $table->enum('dayOfCreditingForMonthlyFrequency', ['FIRST_DAY', 'LAST_DAY', 'MONTHLY_HIRE_DATE'])->nullable();
            $table->enum('accrualValidFrom', ['DATE_OF_ACCRUAL', 'LEAVE_PERIOD_START_DATE'])->nullable();
            $table->enum('firstAccrualForMonthlyFrequency', ['FULL_AMOUNT', 'SKIP', 'FULL_AMOUNT_IF_JOINED_BEFORE_15'])->nullable();
            $table->enum('firstAccrualForAnnualfrequency', ['FULL_AMOUNT', 'SKIP', 'FULL_AMOUNT_IF_JOINED_IN_THE_FIRST_HALF_OF_THE_YEAR', 'PRO_RATE'])->nullable();
            $table->json('proRateMethodFirstAccrualForAnnualFrequency')->nullable();
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
        Schema::dropIfExists('leaveAccrual');
    }
}
