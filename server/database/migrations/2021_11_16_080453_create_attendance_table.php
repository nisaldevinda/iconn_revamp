<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceTable extends Migration
{
    /**
     * Run the migrations
     *  to create attendance table
     * @return void
     */
    public function up()
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date');
            $table->string('timeZone');
            $table->timestamp('in'); // need to save in emp time zone
            $table->timestamp('out')->nullable()->default(null); // need to save in emp time zone

            $table->integer('typeId')->nullable()->default(null); // need to remove if no need to create empty record with leave approved
            $table->integer('employeeId')->nullable()->default(null);
            $table->integer('calendarId')->nullable()->default(null);
            $table->integer('shiftId')->nullable()->default(null);

            $table->integer('extraHours')->nullable()->default(0);
            $table->integer('requiredHours')->nullable()->default(0);
            $table->integer('workedHours')->nullable()->default(0);
            $table->integer('breakHours')->nullable()->default(0);

            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP')); 
            // need to add timezone
            // early OT
            // after OT
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance');
    }
}
