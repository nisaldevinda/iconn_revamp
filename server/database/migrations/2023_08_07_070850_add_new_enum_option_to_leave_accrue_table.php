<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewEnumOptionToLeaveAccrueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaveAccrual', function (Blueprint $table) {
            DB::statement("ALTER TABLE leaveAccrual MODIFY dayOfCreditingForMonthlyFrequency enum('FIRST_DAY', 'LAST_DAY', 'MONTHLY_HIRE_DATE', 'FIRST_ACCRUE_ON_HIRE_DATE_AND_OTHERS_ON_FIRST_DAY_OF_MONTH', 'FIRST_ACCRUE_ON_AFTER_GIVEN_NO_OF_DATES_THEN_MONTHLY_ANIVERSARIES');");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leave_accrue', function (Blueprint $table) {
            DB::statement("ALTER TABLE leaveAccrual MODIFY dayOfCreditingForMonthlyFrequency enum('FIRST_DAY', 'LAST_DAY', 'MONTHLY_HIRE_DATE');");
        });
    }
}
