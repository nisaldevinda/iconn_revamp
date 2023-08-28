<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExpectedWorktimeColumnForAttendanceSummary extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attendance_summary', function (Blueprint $table) {
            $table->integer('expectedWorkTime')->default(0)->after('earlyOut')->comment('expected work time in minutes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendance_summary', function (Blueprint $table) {
            $table->dropColumn('expectedWorkTime');
        });
    }
}
