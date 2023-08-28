<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableCalledWorkflowInstance extends Migration
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
            $table->unsignedInteger('currentStateId')->nullable()->default(null);
            $table->integer('currentApproveLevelSequence')->nullable()->default(null);
            $table->json('levelPermittedEmployees')->nullable()->default(null);
            $table->json('levelPermittedJobCategories')->nullable()->default(null);
            $table->json('levelPermittedDesignations')->nullable()->default(null);
            $table->json('levelPermittedUserRoles')->nullable()->default(null);
            $table->json('levelPermittedCommonOptions')->nullable()->default(null);
            $table->boolean('isDelete')->default(false);
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
        Schema::dropIfExists('workflowInstance');
    }
}
