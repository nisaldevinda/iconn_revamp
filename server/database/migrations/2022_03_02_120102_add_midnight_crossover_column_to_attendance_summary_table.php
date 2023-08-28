<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMidnightCrossoverColumnToAttendanceSummaryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attendance_summary', function (Blueprint $table) {
            $table->boolean('hasMidnightCrossOver')->default(false)->after('shiftId');
            $table->integer('shiftId')->nullable()->default(null)->change();
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
            $table->dropColumn('hasMidnightCrossOver');
        });
    }
}
