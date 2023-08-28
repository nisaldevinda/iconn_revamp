<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLateEarlyTimesToSummaryTable extends Migration
{
    /**
     * add lateIn, earlyOut to attendance summary table
     * @return void
     */
    public function up()
    {
        Schema::table('attendance_summary', function(Blueprint $table) {
            $table->integer('lateIn')->nullable()->default(0)->after('isLateIn');
            $table->integer('earlyOut')->nullable()->default(0)->after('isEarlyOut');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendance_summary', function(Blueprint $table) {
            $table->dropColumn('lateIn');
            $table->dropColumn('earlyOut');
        });
    }
}
