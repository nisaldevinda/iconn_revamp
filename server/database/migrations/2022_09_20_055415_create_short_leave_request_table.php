<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateShortLeaveRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shortLeaveRequest', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('employeeId');
            $table->enum('shortLeaveType', ['IN_SHORT_LEAVE', 'OUT_SHORT_LEAVE']);
            $table->date('date')->nullable()->default(null);
            $table->time('fromTime')->nullable()->default(null);
            $table->time('toTime')->nullable()->default(null);
            $table->string('numberOfMinutes')->nullable()->default(null);
            $table->integer('workflowInstanceId')->nullable()->default(null);
            $table->string('reason')->nullable()->default(null);
            $table->json('fileAttachementIds')->default("[]");
            $table->integer('currentState')->nullable()->default(null);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
            $table->integer('approvedBy')->nullable()->default(null);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('approvedAt')->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shortLeaveRequest');
    }
}
