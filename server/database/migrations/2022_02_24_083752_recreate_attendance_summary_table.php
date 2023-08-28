<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RecreateAttendanceSummaryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //drop existing table

        Schema::dropIfExists('attendance_summary');

        Schema::create('attendance_summary', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date');
            $table->string('timeZone');
            $table->integer('employeeId');
            $table->integer('dayTypeId');
            $table->integer('shiftId');
            $table->boolean('isExpectedToPresent')->default(true);
            $table->double('expectedLeaveAmount', 3, 2)->default(1);
            $table->dateTime('expectedIn')->nullable()->default(null);
            $table->dateTime('expectedOut')->nullable()->default(null);
            $table->dateTime('actualIn')->nullable()->default(null);
            $table->dateTime('actualInUTC')->nullable()->default(null);
            $table->dateTime('actualOut')->nullable()->default(null);
            $table->dateTime('actualOutUTC')->nullable()->default(null);
            $table->boolean('isPresent')->default(false);
            $table->boolean('isLateIn')->default(false);
            $table->boolean('isEarlyOut')->default(false);
            $table->integer('workTime')->default(0)->comment('work time in minutes');
            $table->integer('breakTime')->default(0)->comment('break time in minutes');
            $table->boolean('isFullDayLeave')->default(false);
            $table->boolean('isHalfDayLeave')->default(false);
            $table->boolean('isShortLeave')->default(false);
            $table->boolean('isNoPay')->default(true);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
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
