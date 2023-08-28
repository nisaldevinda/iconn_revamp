<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableCalledWorkFlowApproverLevel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workflowApprovalLevel', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('workflowId')->nullable()->default(null);
            $table->integer('levelSequence')->nullable()->default(null);
            $table->string('levelName')->nullable()->default(null);
            $table->enum('levelType', ['STATIC', 'DYNAMIC', 'POOL'])->nullable()->default(null);
            $table->unsignedBigInteger('staticApproverEmployeeId')->nullable()->default(null);
            $table->enum('dynamicApprovalTypeCategory', ['COMMON', 'JOB_CATEGORY', 'DESIGNATION', 'USER_ROLE'])->nullable()->default(null);
            $table->enum('commonApprovalType', ['REPORTING_PERSON'])->nullable()->default(null);
            $table->json('approverUserRoles')->nullable()->default(null);
            $table->json('approverJobCategories')->nullable()->default(null);
            $table->json('approverDesignation')->nullable()->default(null);
            $table->unsignedBigInteger('approverPoolId')->nullable()->default(null);
            $table->json('approvalLevelActions')->nullable()->default(null);
            $table->boolean('isReadOnly')->default(false);
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
        Schema::dropIfExists('workflowApprovalLevel');
    }
}
