<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeWorkflowTableNames extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('workflow_actions', 'workflowAction');
        Schema::rename('workflow_state', 'workflowState');
        Schema::rename('workflow_context', 'workflowContext');
        Schema::rename('workflow_state_transitions', 'workflowStateTransitions');
        Schema::rename('workflow_define', 'workflowDefine');
        Schema::rename('workflow_role_permission', 'workflowPermission');


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename( 'workflowAction','workflow_actions');
        Schema::rename('workflowState','workflow_state');
        Schema::rename( 'workflowContext','workflow_context');
        Schema::rename( 'workflowStateTransitions','workflow_state_transitions');
        Schema::rename('workflowDefine','workflow_define' );
        Schema::rename('workflowPermission','workflow_role_permission');    }
}
