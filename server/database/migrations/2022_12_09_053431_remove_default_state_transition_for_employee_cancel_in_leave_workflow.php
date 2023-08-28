<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RemoveDefaultStateTransitionForEmployeeCancelInLeaveWorkflow extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            
            $updateDeleteState = DB::table('workflowStateTransitions')
                ->where('workflowId', 2)
                ->where('priorStateId', 1)
                ->where('postStateId', 4)
                ->update(['isDelete' => true]);

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
            
            $updateDeleteState = DB::table('workflowStateTransitions')
                ->where('workflowId', 2)
                ->where('priorStateId', 1)
                ->where('postStateId', 4)
                ->update(['isDelete' => false]);

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
