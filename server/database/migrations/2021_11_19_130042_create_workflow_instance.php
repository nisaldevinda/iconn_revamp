<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkflowInstance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workflowInstance', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('workflowId')->nullable()->default(null);
            $table->string('actionId')->nullable()->default(null);
            $table->unsignedInteger('priorState')->nullable()->default(null);
            $table->unsignedInteger('postState')->nullable()->default(null);
            $table->unsignedInteger('contextId')->nullable()->default(null);
            $table->boolean('isDelete')->default(false);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->foreign('workflowId')->references('id')->on('workflowDefine');
            $table->foreign('priorState')->references('id')->on('workflowState');
            $table->foreign('postState')->references('id')->on('workflowState');
            $table->foreign('contextId')->references('id')->on('workflowContext');

             
        }); 
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('workflow_instance');
    }
}
