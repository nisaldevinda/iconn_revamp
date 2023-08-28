<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateLeaveAccrualEntitlementLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leaveAccrualEntitlementLog', function (Blueprint $table) {
            $table->id();
            $table->integer('leaveAccrualProcessId');
            $table->integer('leaveTypeId');
            $table->integer('employeeId');
            $table->integer('leaveEntitlementId')->nullable()->default(null);
            $table->date('accrualDate');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leaveAccrualEntitlementLog');
    }
}
