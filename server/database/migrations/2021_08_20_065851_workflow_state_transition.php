<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WorkflowStateTransition extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workflow_state_transitions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('workflowId')->nullable()->default(null);
            $table->unsignedInteger('actionId')->nullable()->default(null);
            $table->unsignedInteger('priorStateId')->nullable()->default(null);
            $table->unsignedInteger('postStateId')->nullable()->default(null);
            $table->boolean('isDelete')->default(false);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->foreign('actionId')->references('id')->on('workflow_actions');
            $table->foreign('workflowId')->references('id')->on('workflow_define');
            $table->foreign('priorStateId')->references('id')->on('workflow_state');
            $table->foreign('postStateId')->references('id')->on('workflow_state');
           
        });    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
