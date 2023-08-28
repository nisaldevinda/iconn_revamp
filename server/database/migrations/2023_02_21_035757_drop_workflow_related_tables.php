<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropWorkflowRelatedTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //drop workflow state transitions table
        Schema::dropIfExists('workflowStateTransitions');

        //drop workflowPermission table
        Schema::dropIfExists('workflowPermission');
        
        //drop workflowInstanceDetail table
        Schema::dropIfExists('workflowInstanceDetail');


        //drop workflowDetail table
        Schema::dropIfExists('workflowDetail');
        
        //drop workflowInstance table
        Schema::dropIfExists('workflowInstance');

        //drop workflowAction table
        Schema::dropIfExists('workflowAction');

        //drop workflowDefine table
        Schema::dropIfExists('workflowDefine');
    }
    
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('workflowStateTransitions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('workflowId')->nullable()->default(null);
            $table->unsignedInteger('actionId')->nullable()->default(null);
            $table->unsignedInteger('priorStateId')->nullable()->default(null);
            $table->unsignedInteger('postStateId')->nullable()->default(null);
            $table->json('permittedEmployees')->nullable()->default(null);
            $table->enum('permissionType', ['ROLE_BASE', 'EMPLOYEE_BASE'])->nullable()->default(null);
            $table->json('permittedRoles')->nullable()->default(null);
            $table->boolean('isReadOnly')->default(false);
            $table->boolean('isDelete')->default(false);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
           
        });

        Schema::create('workflowInstanceDetail', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('instanceId')->nullable()->default(null);
            $table->string('actionId')->nullable()->default(null);
            $table->unsignedInteger('priorState')->nullable()->default(null);
            $table->unsignedInteger('postState')->nullable()->default(null);
            $table->unsignedInteger('performUserId')->nullable()->default(null);
            $table->boolean('isDelete')->default(false);
            $table->string('approverComment')->nullable()->default(null)->after('performUserId');
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP')); 
        });

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
            $table->json('actionPermittedEmployees')->nullable()->default(null);
            $table->json('actionPermittedRoles')->nullable()->default(null);
        });

        Schema::create('workflowAction', function (Blueprint $table) {
            $table->increments('id');
            $table->string('actionName')->nullable()->default(null);
            $table->string('label')->nullable()->default(null);
            $table->string('description')->nullable()->default(null); 
            $table->boolean('isPrimary')->default(false);
            $table->boolean('isDelete')->default(false);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
        });

        Schema::create('workflowDefine', function (Blueprint $table) {
            $table->increments('id');
            $table->string('workflowName')->nullable()->default(null);
            $table->string('description')->nullable()->default(null);
            $table->unsignedInteger('contextId')->nullable()->default(null);
            $table->boolean('isDelete')->default(false);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->foreign('contextId')->references('id')->on('workflow_context');
            $table->json('sucessStates')->default("[]");
            $table->json('failureStates')->default("[]");
            $table->integer('employeeGroupId')->nullable()->default(null);
            $table->boolean('isReadOnly')->default(false); 
        }); 
    }
}
