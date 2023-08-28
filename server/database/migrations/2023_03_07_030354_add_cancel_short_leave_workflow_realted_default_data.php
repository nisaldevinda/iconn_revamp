<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCancelShortLeaveWorkflowRealtedDefaultData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $contextData = array(
            [
                'id'=> 8,
                'contextName' => 'Cancel Short Leave',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ]
        );

        $workflowDefineData = array(
            [
                'id'=> 8,
                'workflowName' => 'Default Cancel Short Leave Request Workflow',
                'description' => 'Default Workflow For Handle Cancel Short Leave Requests',
                'contextId' => 8,
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


        $workflowApprovalData = array(
            [
                'id'=> 8,
                'workflowId' => 8,
                'levelSequence' => 1,
                'levelName' => 'Approval_Level_1',
                'levelType' => 'DYNAMIC',
                'dynamicApprovalTypeCategory' => 'COMMON',
                'commonApprovalType' => 'REPORTING_PERSON',
                'approvalLevelActions' => json_encode([2,3]),
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            
            
        );


        try {

             //insert resignation context
             foreach ($contextData as $contextKey => $context) {
                $context = (array) $context;
               
                $record = DB::table('workflowContext')->where('id', $context['id'])->first();
                
                if (is_null($record)) {
                    DB::table('workflowContext')->insert($context);
                }
    
            }


            //insert default workflow define record for cancel short leave request
            foreach ($workflowDefineData as $workflowDefineKey => $workflowDefine) {
                $workflowDefine = (array) $workflowDefine;
               
                $record = DB::table('workflowDefine')->where('id', $workflowDefine['id'])->first();
    
                if (is_null($record)) {
                    DB::table('workflowDefine')->insert($workflowDefine);
                }
    
                
            }

            //insert default workflow define approval level record for cancel short leave request
            foreach ($workflowApprovalData as $workflowApprovalKey => $workflowApproval) {
                $workflowApproval = (array) $workflowApproval;
               
                $record = DB::table('workflowApprovalLevel')->where('id', $workflowApproval['id'])->first();
    
                if (is_null($record)) {
                    DB::table('workflowApprovalLevel')->insert($workflowApproval);
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
        $contextData = array(
            [
                'id'=> 8,
                'contextName' => 'Cancel Short Leave',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ]
        );

        $workflowDefineData = array(
            [
                'id'=> 8,
                'workflowName' => 'Default Cancel Short Leave Request Workflow',
                'description' => 'Default Workflow For Handle Cancel Short Leave Requests',
                'contextId' => 8,
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


        $workflowApprovalData = array(
            [
                'id'=> 8,
                'workflowId' => 8,
                'levelSequence' => 1,
                'levelName' => 'Approval_Level_1',
                'levelType' => 'DYNAMIC',
                'dynamicApprovalTypeCategory' => 'COMMON',
                'commonApprovalType' => 'REPORTING_PERSON',
                'approvalLevelActions' => json_encode([2,3]),
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            
            
        );


        try {

             //insert resignation context
             foreach ($contextData as $contextKey => $context) {
                $context = (array) $context;
               
                $record = DB::table('workflowContext')->where('id', $context['id'])->first();
                
                if (!is_null($record)) {
                    DB::table('workflowContext')->where('id', $context['id'])->delete();
                }
    
            }


            //insert default workflow define record for cancel short leave request
            foreach ($workflowDefineData as $workflowDefineKey => $workflowDefine) {
                $workflowDefine = (array) $workflowDefine;
               
                $record = DB::table('workflowDefine')->where('id', $workflowDefine['id'])->first();
    
                if (!is_null($record)) {
                    DB::table('workflowDefine')->where('id', $workflowDefine['id'])->delete();
                }
    
                
            }

            //insert default workflow define approval level record for cancel short leave request
            foreach ($workflowApprovalData as $workflowApprovalKey => $workflowApproval) {
                $workflowApproval = (array) $workflowApproval;
               
                $record = DB::table('workflowApprovalLevel')->where('id', $workflowApproval['id'])->first();
    
                if (!is_null($record)) {
                    DB::table('workflowApprovalLevel')->where('id', $workflowApproval['id'])->delete();
                }
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
