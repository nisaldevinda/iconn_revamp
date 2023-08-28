<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddCarriedForwardFlagIntoLeaveEntitlement extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaveEntitlement', function($table) {
            $table->date('validTo')->after('leavePeriodTo');
            $table->date('validFrom')->after('leavePeriodTo');
            $table->enum('type', ['CARRY_FORWARD', 'MANUAL', 'ACCRUAL'])->default('MANUAL')->after('leavePeriodTo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leaveEntitlement', function($table) {
            $table->dropColumn('validFrom');
            $table->dropColumn('validTo');
            $table->dropColumn('type');
        });
    }
}
