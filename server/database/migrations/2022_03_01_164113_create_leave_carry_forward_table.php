<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveCarryForwardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leaveCarryForward', function (Blueprint $table) {
            $table->id();
            $table->integer('leaveTypeId');
            $table->integer('leaveEmployeeGroupId');
            $table->boolean('includeOverdrawnLeave')->default(false);
            $table->boolean('carryForwardNegativeBalance')->default(false);
            $table->integer('maximumNumberToCarryForward')->nullable();
            $table->integer('expireAfter')->length(2);
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
        Schema::dropIfExists('leaveCarryForward');
    }
}
