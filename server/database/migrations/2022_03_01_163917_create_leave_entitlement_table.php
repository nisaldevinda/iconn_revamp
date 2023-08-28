<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveEntitlementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leaveEntitlement', function (Blueprint $table) {
            $table->id();
            $table->integer('employeeId');
            $table->integer('leaveTypeId');
            $table->date('leavePeriodFrom');
            $table->date('leavePeriodTo');
            $table->float('entilementCount', 5, 2);	
            $table->float('pendingCount', 5, 2)->default(0);
            $table->float('usedCount', 5, 2)->default(0);
            $table->string('comment')->nullable();
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leaveEntitlement');
    }
}
