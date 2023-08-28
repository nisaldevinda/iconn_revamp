<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSummaryIdTimeChangeColumn extends Migration
{
    /**
     * add attendance summary table id
     * @return void
     */
    public function up()
    {
        Schema::table('time_change_requests', function(Blueprint $table) {
            $table->integer('summaryId')->after('employeeId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_change_requests', function(Blueprint $table) {
            $table->dropColumn('summaryId');
        });
    }
}
