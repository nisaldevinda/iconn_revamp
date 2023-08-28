<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeDeafultDayTypesReadOnly extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            // update working day daytype
            DB::table('workCalendarDayType')->where('id', 1)->update(["isReadOnly" => true, 'typeColor' => '#86C129']);

             // update non working day daytype
            DB::table('workCalendarDayType')->where('id', 2)->update(["isReadOnly" => true, 'typeColor' => '#4F4D46']);


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
        // update working day daytype
        DB::table('workCalendarDayType')->where('id', 1)->update(["isReadOnly" => false]);

        // update non working day daytype
        DB::table('workCalendarDayType')->where('id', 2)->update(["isReadOnly" => false]);
    }
}
