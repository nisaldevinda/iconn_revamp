<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SetLeaveEntitlementIdColumnAsNullableInLeaveRequestEntitlementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaveRequestEntitlement', function (Blueprint $table) {
            $table->integer('leaveEntitlementId')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leaveRequestEntitlement', function (Blueprint $table) {
            $table->integer('leaveEntitlementId')->unsigned()->nullable(false)->change();
        });
    }
}
