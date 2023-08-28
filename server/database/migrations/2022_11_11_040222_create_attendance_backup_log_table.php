<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceBackupLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_backup_log', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('recordId')->nullable()->default(null);
            $table->string('recNo')->nullable()->default(null);
            $table->string('deviceId')->nullable()->default(null);
            $table->string('attendanceId')->nullable()->default(null);
            $table->string('type')->nullable()->default(null);
            $table->string('mode')->nullable()->default(null);
            $table->string('recordTimeString')->nullable()->default(null);
            $table->dateTime('recordTime')->nullable()->default(null);
            $table->dateTime('readDateTime')->nullable()->default(null);
            $table->string('client')->nullable()->default(null);
            $table->string('location')->nullable()->default(null);
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
        Schema::dropIfExists('attendance_backup_log');
    }
}
