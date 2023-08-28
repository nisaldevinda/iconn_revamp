<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeApprovalLevelNameOfDefaultWorkflows extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // get approval levels of default workflows
        $applrovalLevelRecords = (array) DB::table('workflowApprovalLevel')->whereIn('id', [1,2,3,4,5,6,7,8])->get();

        if (sizeof($applrovalLevelRecords) > 0) {
            //update approval level name of defauld workflow approval levels
            DB::table('workflowApprovalLevel')
            ->whereIn('id', [1,2,3,4,5,6,7,8])
            ->update(['levelName' => 'Approve Level 1']);
        }
    
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // get approval levels of default workflows
        $applrovalLevelRecords = (array) DB::table('workflowApprovalLevel')->whereIn('id', [1,2,3,4,5,6,7,8])->get();

        if (sizeof($applrovalLevelRecords) > 0) {
            //update approval level name of defauld workflow approval levels
            DB::table('workflowApprovalLevel')
            ->whereIn('id', [1,2,3,4,5,6,7,8])
            ->update(['levelName' => 'Approval_Level_1']);
        }
    }
}
