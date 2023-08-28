<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkflowInstanceDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workflowDetail', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('employeeId')->nullable()->default(null);
            $table->unsignedInteger('instanceId')->nullable()->default(null);
            $table->json('details')->default('{}');;
            $table->boolean('isDelete')->default(false);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->foreign('employeeId')->references('id')->on('employee');
            $table->foreign('instanceId')->references('id')->on('workflowInstance');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('workflowDetail');
    }
}
