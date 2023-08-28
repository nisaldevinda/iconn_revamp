<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertDefaultWorkflowApprovalLevelDetailsForDefaultWorkflows extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $workflowApprovalData = array(
            [
                'id'=> 1,
                'workflowId' => 1,
                'levelSequence' => 1,
                'levelName' => 'Approval_Level_1',
                'levelType' => 'DYNAMIC',
                'dynamicApprovalTypeCategory' => 'USER_ROLE',
                'approverUserRoles' => json_encode([1]),
                'approvalLevelActions' => json_encode([2,3]),
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            [
                'id'=> 2,
                'workflowId' => 2,
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
            [
                'id'=> 3,
                'workflowId' => 3,
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
            [
                'id'=> 4,
                'workflowId' => 4,
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
            [
                'id'=> 5,
                'workflowId' => 5,
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
            [
                'id'=> 6,
                'workflowId' => 6,
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
            [
                'id'=> 7,
                'workflowId' => 7,
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
        $workflowApprovalData = array(
            [
                'id'=> 1,
                'workflowId' => 1,
                'levelSequence' => 1,
                'levelName' => 'Approval_Level_1',
                'levelType' => 'DYNAMIC',
                'dynamicApprovalTypeCategory' => 'USER_ROLE',
                'approverUserRoles' => json_encode([1]),
                'approvalLevelActions' => json_encode([2,3]),
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            [
                'id'=> 2,
                'workflowId' => 2,
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
            [
                'id'=> 3,
                'workflowId' => 3,
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
            [
                'id'=> 4,
                'workflowId' => 4,
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
            [
                'id'=> 5,
                'workflowId' => 5,
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
            [
                'id'=> 6,
                'workflowId' => 6,
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
            [
                'id'=> 7,
                'workflowId' => 7,
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
