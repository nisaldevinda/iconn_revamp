<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccrualConfigForHandleWhenEmployeeAddedToThePermenentCarderInMidOfTheYear extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $configuration = [
            "id" => 14,
            "key" => "leave-accrue-config-for-emp-added-to-permenent-carder-in-mid-of-the-year",
            "description" => "Leave Accrual Configuration For Employees That Added To the Permenent Carder in the Mid Of the Year",
            "type" => "json",
            "value" => json_encode([
                "leaveTypes" => [],
                "dayOfCreditingForMonthlyFrequency" => "FIRST_DAY",
                "accrualValidFrom" => "DATE_OF_ACCRUAL",
                "monthlyAllocatedLeaveAmount" => 1,
                "firstAccrualForMonthlyFrequency" => 'FULL_AMOUNT',
            ])
        ];

        $accueConfig = DB::table('configuration')->where('id', '=', 14)->first();

        if (Schema::hasTable('configuration') && is_null($accueConfig)) {
            DB::table('configuration')->insert($configuration);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('configuration')->where('id', '=', 14)->delete();        
    }
}
