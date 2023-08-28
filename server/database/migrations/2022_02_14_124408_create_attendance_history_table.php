<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceHistoryTable extends Migration
{
    /**
     * Run the migrations
     *  to move attendance records here after time change request accepted
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_history', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date');
            $table->string('timeZone');

            $table->timestamp('in'); // in emp time zone
            $table->timestamp('out')->nullable()->default(null); // in emp time zone
            $table->timestamp('inUTC'); // in UTC time zone
            $table->timestamp('outUTC')->nullable()->default(null); // in UTC time zone

            $table->integer('typeId')->nullable()->default(null); // need to remove if no need to create empty record with leave approved
            $table->integer('attendanceId')->nullable()->default(null); // old attendance table id to link with break history old break id no any other relationship with this filed

            $table->integer('earlyOut')->nullable()->default(0);
            $table->integer('lateIn')->nullable()->default(0);
            $table->integer('workedHours')->nullable()->default(0);
            $table->integer('breakHours')->nullable()->default(0);

            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_history');
    }
}
