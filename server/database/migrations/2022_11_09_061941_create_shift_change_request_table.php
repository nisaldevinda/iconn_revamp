<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateShiftChangeRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shiftChangeRequest', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('employeeId');
            $table->date('shiftDate')->nullable()->default(null);
            $table->integer('relateAdhocShiftId')->nullable()->default(null);
            $table->integer('currentShiftId')->nullable()->default(null);
            $table->integer('newShiftId')->nullable()->default(null);
            $table->integer('workflowInstanceId')->nullable()->default(null);
            $table->string('reason')->nullable()->default(null);
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
        Schema::dropIfExists('shiftChangeRequest');
    }
}
