<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeWorkflowDefaultConfigurationsReadOnly extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            $workflowDefineIdArray = [1, 2, 3];
            $workflowContextIdArray = [1, 2, 3];
            $workflowStateTransitionIdArray = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

            // update workflow define
            DB::table('workflowDefine')->whereIn('id', $workflowDefineIdArray)->update(["isReadOnly" => true]);

            // update workflow context
            DB::table('workflowContext')->whereIn('id', $workflowContextIdArray)->update(["isReadOnly" => true]);

            // update workflow context
            DB::table('workflowStateTransitions')->whereIn('id', $workflowStateTransitionIdArray)->update(["isReadOnly" => true]);

        } catch (\Throwable $th) {
            throw $th;
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        try {
            $workflowDefineIdArray = [1, 2, 3];
            $workflowContextIdArray = [1, 2, 3];
            $workflowStateTransitionIdArray = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

            // update workflow define
            DB::table('workflowDefine')->whereIn('id', $workflowDefineIdArray)->update(["isReadOnly" => false]);

            // update workflow context
            DB::table('workflowContext')->whereIn('id', $workflowContextIdArray)->update(["isReadOnly" => false]);

            // update workflow context
            DB::table('workflowStateTransitions')->whereIn('id', $workflowStateTransitionIdArray)->update(["isReadOnly" => false]);

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
