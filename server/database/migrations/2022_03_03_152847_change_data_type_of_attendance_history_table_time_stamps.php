<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDataTypeOfAttendanceHistoryTableTimeStamps extends Migration
{
    /**
     * Run the migrations.
     * change timeStamp fields to dateTime
     * @return void
     */
    public function up()
    {
        Schema::table('attendance_history', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_history', 'in')) {
                $table->dateTime('in')->change();
            }

            if (Schema::hasColumn('attendance_history', 'inUTC')) {
                $table->dateTime('inUTC')->change();
            }

            if (Schema::hasColumn('attendance_history', 'out')) {
                $table->dateTime('out')->change();
            }

            if (Schema::hasColumn('attendance_history', 'outUTC')) {
                $table->dateTime('outUTC')->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendance_history', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_history', 'in')) {
                $table->timestamp('in')->change();
            }

            if (Schema::hasColumn('attendance_history', 'inUTC')) {
                $table->timestamp('inUTC')->change();
            }

            if (Schema::hasColumn('attendance_history', 'out')) {
                $table->timestamp('out')->change();
            }

            if (Schema::hasColumn('attendance_history', 'outUTC')) {
                $table->timestamp('outUTC')->change();
            }
        });
    }
}
