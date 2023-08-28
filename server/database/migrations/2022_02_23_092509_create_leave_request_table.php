<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leaveRequest', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('employeeId');
            $table->integer('leaveTypeId');
            $table->string('leavePeriodType')->nullable()->default('fullDay');
            $table->date('fromDate')->nullable()->default(null);
            $table->date('toDate')->nullable()->default(null);
            $table->time('fromTime')->nullable()->default(null);
            $table->time('toTime')->nullable()->default(null);
            $table->string('numberOfLeaveDates')->nullable()->default(null);
            $table->integer('workflowInstanceId')->nullable()->default(null);
            $table->string('reason')->nullable()->default(null);
            $table->json('fileAttachementIds')->default("[]");
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
        Schema::dropIfExists('leaveRequest');
    }
}
