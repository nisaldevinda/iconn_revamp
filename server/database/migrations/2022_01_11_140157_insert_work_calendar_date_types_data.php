<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertWorkCalendarDateTypesData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $data = array(
            [
                'id' => 1,
                'name' => 'Working Day',
                'typeColor' => 'success'
            ],
            [
                'id' => 2,
                'name' => 'Non Working Day',
                'typeColor' => 'warning'
            ],
            [
                'id' => 3,
                'name' => 'Holiday',
                'typeColor' => 'error'
            ]
        );

        try {
            $record = DB::table('workCalendarDateType')->where('id', 1)->first();
            if ($record) {
                return ('Work Calendar date type already exists');
            }

            DB::table('workCalendarDateType')->insert($data);
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
            $record = DB::table('workCalendarDateType')->where('id', 1)->first();

            if (empty($record)) {
                return ('Work Calendar date type already exists');
            }

            $affectedRows  = DB::table('workCalendarDateType')->where('id', 1)->delete();

            return ($affectedRows) ? true : false;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
