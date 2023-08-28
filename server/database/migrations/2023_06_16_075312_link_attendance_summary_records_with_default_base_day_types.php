<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LinkAttendanceSummaryRecordsWithDefaultBaseDayTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {

            //assign working day base day type for attendance summary table records that day type equal to working day
            $updateWorkigDayType = DB::table('attendance_summary')->where('dayTypeId', 1)->update(['baseDayType' => 1]);

            //assign non working day base day type for work calandar default day type called non working day
            $updateWorkigDayType = DB::table('attendance_summary')->where('dayTypeId', 2)->update(['baseDayType' => 2]);

           
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        try {

            //assign working day base day type for attendance summary table records that day type equal to working day
            $updateWorkigDayType = DB::table('attendance_summary')->where('dayTypeId', 1)->update(['baseDayType' => null]);

            //assign non working day base day type for work calandar default day type called non working day
            $updateWorkigDayType = DB::table('attendance_summary')->where('dayTypeId', 2)->update(['baseDayType' => null]);

           
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
