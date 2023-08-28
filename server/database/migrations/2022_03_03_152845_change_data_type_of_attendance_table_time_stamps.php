<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDataTypeOfAttendanceTableTimeStamps extends Migration
{
    /**
     * Run the migrations.
     * change timeStamp fields to dateTime
     * @return void
     */
    public function up()
    {
        Schema::table('attendance', function (Blueprint $table) {
            if (Schema::hasColumn('attendance', 'in')) {
                $table->dateTime('in')->change();
            }

            if (Schema::hasColumn('attendance', 'inUTC')) {
                $table->dateTime('inUTC')->change();
            }

            if (Schema::hasColumn('attendance', 'out')) {
                $table->dateTime('out')->change();
            }

            if (Schema::hasColumn('attendance', 'outUTC')) {
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
        Schema::table('attendance', function (Blueprint $table) {
            if (Schema::hasColumn('attendance', 'in')) {
                $table->timestamp('in')->change();
            }

            if (Schema::hasColumn('attendance', 'inUTC')) {
                $table->timestamp('inUTC')->change();
            }

            if (Schema::hasColumn('attendance', 'out')) {
                $table->timestamp('out')->change();
            }

            if (Schema::hasColumn('attendance', 'outUTC')) {
                $table->timestamp('outUTC')->change();
            }
        });
    }
}
