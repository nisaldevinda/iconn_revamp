<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewFlagCalledIsAllowedShortLeave extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaveType', function (Blueprint $table) {
            $table->boolean('shortLeaveAllowed')->default(false)->after('timeDurationAllowed');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leaveType', function (Blueprint $table) {
            $table->dropColumn('shortLeaveAllowed');
        });
    }
}
