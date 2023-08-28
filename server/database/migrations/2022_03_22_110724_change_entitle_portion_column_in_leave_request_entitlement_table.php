<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeEntitlePortionColumnInLeaveRequestEntitlementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaveRequestEntitlement', function (Blueprint $table) {
            $table->renameColumn('entitlePotion', 'entitlePortion');
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
            $table->renameColumn('entitlePortion', 'entitlePotion');
        });
    }
}
