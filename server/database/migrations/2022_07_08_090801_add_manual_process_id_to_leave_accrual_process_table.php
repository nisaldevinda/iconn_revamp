<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddManualProcessIdToLeaveAccrualProcessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaveAccrualProcess', function (Blueprint $table) {
            $table->integer('manualProcessId')->nullable()->default(null)->after('numberOfAllocatedEntitlements');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leaveAccrualProcess', function (Blueprint $table) {
            $table->dropColumn('manualProcessId');
        });
    }
}
