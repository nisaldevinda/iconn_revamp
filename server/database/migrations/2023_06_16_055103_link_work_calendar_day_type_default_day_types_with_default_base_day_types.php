<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LinkWorkCalendarDayTypeDefaultDayTypesWithDefaultBaseDayTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {

            //assign working day base day type for work calandar default day type called working day
            $updateWorkigDayType = DB::table('workCalendarDayType')->where('id', 1)->update(['baseDayTypeId' => 1]);

            //assign non working day base day type for work calandar default day type called non working day
            $updateWorkigDayType = DB::table('workCalendarDayType')->where('id', 2)->update(['baseDayTypeId' => 2]);

           
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

            //assign working day base day type for work calandar default day type called working day
            $updateWorkigDayType = DB::table('workCalendarDayType')->where('id', 1)->update(['baseDayTypeId' => null]);

            //assign non working day base day type for work calandar default day type called non working day
            $updateWorkigDayType = DB::table('workCalendarDayType')->where('id', 2)->update(['baseDayTypeId' => null]);

           
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
