<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceSummaryTable extends Migration
{
    /**
     * Run the migrations
     *  Main attendance summary table
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_summary', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date');
            $table->string('timeZone');

            $table->timestamp('firstIn'); // in emp time zone
            $table->timestamp('lastOut')->nullable()->default(null); // in emp time zone
            $table->timestamp('firstInUTC'); 
            $table->timestamp('lastOutUTC')->nullable()->default(null);

            $table->integer('employeeId')->nullable()->default(null); 
            $table->integer('shiftId')->nullable()->default(null); 
            $table->integer('dayType')->nullable()->default(null); 

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
        Schema::dropIfExists('attendance_summary');
    }
}
