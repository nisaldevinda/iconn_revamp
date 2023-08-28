<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSummaryIdAttendanceColumn extends Migration
{
    /**
     * add attendance summary table id
     * @return void
     */
    public function up()
    {
        Schema::table('attendance', function(Blueprint $table) {
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
        Schema::table('attendance', function(Blueprint $table) {
            $table->dropColumn('summaryId');
        });
    }
}
