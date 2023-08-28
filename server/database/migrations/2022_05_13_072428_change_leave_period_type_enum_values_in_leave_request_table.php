<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeLeavePeriodTypeEnumValuesInLeaveRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leave_request', function (Blueprint $table) {
            DB::statement("ALTER TABLE leaveRequest MODIFY leavePeriodType enum('FULL_DAY', 'FIRST_HALF_DAY', 'SECOND_HALF_DAY', 'IN_SHORT_LEAVE', 'OUT_SHORT_LEAVE', 'SPECIAL_TIME') NOT null;");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leave_request', function (Blueprint $table) {
            DB::statement("ALTER TABLE leaveRequest MODIFY leavePeriodType enum('FULL_DAY', 'FIRST_HALF_DAY', 'SECOND_HALF_DAY', 'SHORT_LEAVE', 'SPECIAL_TIME') NOT null;");
        });
    }
}
