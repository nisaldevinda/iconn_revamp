<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAttendanceSummaryPayTypeDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendanceSummaryPayTypeDetail', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('summaryId');
            $table->integer('payTypeId');
            $table->integer('workedTime')->default(0)->comment('worked time according to pay type');;
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendanceSummaryPayTypeDetail');
    }

}
