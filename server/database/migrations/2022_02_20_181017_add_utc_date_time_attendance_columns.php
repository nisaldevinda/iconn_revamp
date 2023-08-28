<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUtcDateTimeAttendanceColumns extends Migration
{
    /**
     * add UTC in & out.
     * @return void
     */
    public function up()
    {
        Schema::table('attendance', function(Blueprint $table) {
            $table->timestamp('inUTC')->nullable()->default(null)->after('in');
            $table->timestamp('outUTC')->nullable()->default(null)->after('out');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendance', function(Blueprint $table) {
            $table->dropColumn('inUTC');
            $table->dropColumn('outUTC');
        });
    }
}
