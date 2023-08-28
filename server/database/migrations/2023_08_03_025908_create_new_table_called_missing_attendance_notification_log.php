<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewTableCalledMissingAttendanceNotificationLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('missingAttendanceNotificationLog', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('summaryId');
            $table->date('attendanceDate');
            $table->date('sentEmail');
            $table->timestamp('emailSentAt')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('missingAttendanceNotificationLog');
    }
}
