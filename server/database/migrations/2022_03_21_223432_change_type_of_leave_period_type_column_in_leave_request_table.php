<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeTypeOfLeavePeriodTypeColumnInLeaveRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaveRequest', function (Blueprint $table) {
            DB::statement("ALTER TABLE leaveRequest MODIFY leavePeriodType enum('fullDay', 'firstHalfDay', 'secondHalfDay', 'shortLeave', 'specialTime', 'FULL_DAY', 'FIRST_HALF_DAY', 'SECOND_HALF_DAY', 'SHORT_LEAVE', 'SPECIAL_TIME') NOT NULL;");
            DB::statement("UPDATE `leaveRequest` set `leavePeriodType` = 'FULL_DAY' where `leavePeriodType` = 'fullDay';");
            DB::statement("UPDATE `leaveRequest` set `leavePeriodType` = 'FIRST_HALF_DAY' where `leavePeriodType` = 'firstHalfDay';");
            DB::statement("UPDATE `leaveRequest` set `leavePeriodType` = 'SECOND_HALF_DAY' where `leavePeriodType` = 'secondHalfDay';");
            DB::statement("UPDATE `leaveRequest` set `leavePeriodType` = 'SHORT_LEAVE' where `leavePeriodType` = 'shortLeave';");
            DB::statement("UPDATE `leaveRequest` set `leavePeriodType` = 'SPECIAL_TIME' where `leavePeriodType` = 'specialTime';");
            DB::statement("ALTER TABLE leaveRequest MODIFY leavePeriodType enum('FULL_DAY', 'FIRST_HALF_DAY', 'SECOND_HALF_DAY', 'SHORT_LEAVE', 'SPECIAL_TIME') NOT null;");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leaveRequest', function (Blueprint $table) {
            DB::statement("ALTER TABLE leaveRequest MODIFY leavePeriodType enum('fullDay', 'firstHalfDay', 'secondHalfDay', 'shortLeave', 'specialTime', 'FULL_DAY', 'FIRST_HALF_DAY', 'SECOND_HALF_DAY', 'SHORT_LEAVE', 'SPECIAL_TIME') NOT NULL;");
            DB::statement("UPDATE `leaveRequest` set `leavePeriodType` = 'fullDay' where `leavePeriodType` = 'FULL_DAY';");
            DB::statement("UPDATE `leaveRequest` set `leavePeriodType` = 'firstHalfDay' where `leavePeriodType` = 'FIRST_HALF_DAY';");
            DB::statement("UPDATE `leaveRequest` set `leavePeriodType` = 'secondHalfDay' where `leavePeriodType` = 'SECOND_HALF_DAY';");
            DB::statement("UPDATE `leaveRequest` set `leavePeriodType` = 'shortLeave' where `leavePeriodType` = 'SHORT_LEAVE';");
            DB::statement("UPDATE `leaveRequest` set `leavePeriodType` = 'specialTime' where `leavePeriodType` = 'SPECIAL_TIME';");
            DB::statement("ALTER TABLE leaveRequest MODIFY leavePeriodType enum('fullDay', 'firstHalfDay', 'secondHalfDay', 'shortLeave', 'specialTime') NOT null;");
        });
    }
}
