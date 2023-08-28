<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertDefaultWorkflowsForWorkflowDefineTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $workflowDefineData = array(
            [
                'id'=> 1,
                'workflowName' => 'Default Profile Change Request Workflow',
                'description' => 'Default Workflow For Handle Profile Change Requests',
                'contextId' => 1,
                'isAllowToCancelRequestByRequester' => true,
                'numOfApprovalLevels' => 1,
                'isProcedureDefined' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            [
                'id'=> 2,
                'workflowName' => 'Default Leave Request Workflow',
                'description' => 'Default Workflow For Handle Leave Requests',
                'contextId' => 2,
                'isAllowToCancelRequestByRequester' => false,
                'numOfApprovalLevels' => 1,
                'isProcedureDefined' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            [
                'id'=> 3,
                'workflowName' => 'Default Time Change Request Workflow',
                'description' => 'Default Workflow For Handle Time Change Requests',
                'contextId' => 3,
                'isAllowToCancelRequestByRequester' => true,
                'numOfApprovalLevels' => 1,
                'isProcedureDefined' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            [
                'id'=> 4,
                'workflowName' => 'Default Short Leave Request Workflow',
                'description' => 'Default Workflow For Handle Short Leave Requests',
                'contextId' => 4,
                'isAllowToCancelRequestByRequester' => false,
                'numOfApprovalLevels' => 1,
                'isProcedureDefined' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            [
                'id'=> 5,
                'workflowName' => 'Default Shift Change Request Workflow',
                'description' => 'Default Workflow For Handle Shift Change Requests',
                'contextId' => 5,
                'isAllowToCancelRequestByRequester' => true,
                'numOfApprovalLevels' => 1,
                'isProcedureDefined' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            [
                'id'=> 6,
                'workflowName' => 'Default Cancel Leave Request Workflow',
                'description' => 'Default Workflow For Handle Cancel Leave Requests',
                'contextId' => 6,
                'isAllowToCancelRequestByRequester' => true,
                'numOfApprovalLevels' => 1,
                'isProcedureDefined' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            [
                'id'=> 7,
                'workflowName' => 'Default Resignation Request Workflow',
                'description' => 'Default Workflow For Handle Resignation Requests',
                'contextId' => 7,
                'isAllowToCancelRequestByRequester' => true,
                'numOfApprovalLevels' => 1,
                'isProcedureDefined' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            
        );


        try {
            foreach ($workflowDefineData as $workflowDefineKey => $workflowDefine) {
                $workflowDefine = (array) $workflowDefine;
               
                $record = DB::table('workflowDefine')->where('id', $workflowDefine['id'])->first();
    
                if (is_null($record)) {
                    DB::table('workflowDefine')->insert($workflowDefine);
                }
    
                
            }
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
        $workflowDefineData = array(
            [
                'id'=> 1,
                'workflowName' => 'Default Profile Change Request Workflow',
                'description' => 'Default Workflow For Handle Profile Change Requests',
                'contextId' => 1,
                'isAllowToCancelRequestByRequester' => true,
                'numOfApprovalLevels' => 1,
                'isProcedureDefined' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            [
                'id'=> 2,
                'workflowName' => 'Default Leave Request Workflow',
                'description' => 'Default Workflow For Handle Leave Requests',
                'contextId' => 2,
                'isAllowToCancelRequestByRequester' => false,
                'numOfApprovalLevels' => 1,
                'isProcedureDefined' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            [
                'id'=> 3,
                'workflowName' => 'Default Time Change Request Workflow',
                'description' => 'Default Workflow For Handle Time Change Requests',
                'contextId' => 3,
                'isAllowToCancelRequestByRequester' => true,
                'numOfApprovalLevels' => 1,
                'isProcedureDefined' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            [
                'id'=> 4,
                'workflowName' => 'Default Short Leave Request Workflow',
                'description' => 'Default Workflow For Handle Short Leave Requests',
                'contextId' => 4,
                'isAllowToCancelRequestByRequester' => false,
                'numOfApprovalLevels' => 1,
                'isProcedureDefined' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            [
                'id'=> 5,
                'workflowName' => 'Default Shift Change Request Workflow',
                'description' => 'Default Workflow For Handle Shift Change Requests',
                'contextId' => 5,
                'isAllowToCancelRequestByRequester' => true,
                'numOfApprovalLevels' => 1,
                'isProcedureDefined' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            [
                'id'=> 6,
                'workflowName' => 'Default Cancel Leave Request Workflow',
                'description' => 'Default Workflow For Handle Cancel Leave Requests',
                'contextId' => 6,
                'isAllowToCancelRequestByRequester' => true,
                'numOfApprovalLevels' => 1,
                'isProcedureDefined' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            [
                'id'=> 7,
                'workflowName' => 'Default Resignation Request Workflow',
                'description' => 'Default Workflow For Handle Resignation Requests',
                'contextId' => 7,
                'isAllowToCancelRequestByRequester' => true,
                'numOfApprovalLevels' => 1,
                'isProcedureDefined' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            
        );


        try {
            foreach ($workflowDefineData as $workflowDefineKey => $workflowDefine) {
                $workflowDefine = (array) $workflowDefine;
               
                $record = DB::table('workflowDefine')->where('id', $workflowDefine['id'])->first();
    
                if (!is_null($record)) {
                    DB::table('workflowDefine')->where('id', $workflowDefine['id'])->delete();
                }
    
                
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
