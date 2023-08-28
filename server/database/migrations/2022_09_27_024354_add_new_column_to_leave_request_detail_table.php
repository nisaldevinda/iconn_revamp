<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnToLeaveRequestDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaveRequestDetail', function (Blueprint $table) {
            $table->enum('leavePeriodType', ['FULL_DAY', 'FIRST_HALF_DAY', 'SECOND_HALF_DAY', 'IN_SHORT_LEAVE', 'OUT_SHORT_LEAVE', 'SPECIAL_TIME']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leaveRequestDetail', function (Blueprint $table) {
            $table->dropColumn('leavePeriodType');
        });
    }
}
