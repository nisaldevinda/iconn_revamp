<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToAttendanceSummaryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attendance_summary', function (Blueprint $table) {
            $table->integer('preShiftWorkTime')->default(0)->after('workTime')->comment('pre shift work time in minutes');
            $table->integer('withinShiftWorkTime')->default(0)->after('preShiftWorkTime')->comment('within shift work time in minutes');
            $table->integer('postShiftWorkTime')->default(0)->after('withinShiftWorkTime')->comment('post shift work time in minutes');
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
            $table->dropColumn('preShiftWorkTime');
            $table->dropColumn('withinShiftWorkTime');
            $table->dropColumn('postShiftWorkTime');
        });
    }
}
