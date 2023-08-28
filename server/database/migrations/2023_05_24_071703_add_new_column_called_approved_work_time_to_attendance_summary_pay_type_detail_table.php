<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnCalledApprovedWorkTimeToAttendanceSummaryPayTypeDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attendanceSummaryPayTypeDetail', function (Blueprint $table) {
            $table->integer('approvedWorkTime')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendanceSummaryPayTypeDetail', function (Blueprint $table) {
            $table->dropColumn('approvedWorkTime');
        });
    }
}
