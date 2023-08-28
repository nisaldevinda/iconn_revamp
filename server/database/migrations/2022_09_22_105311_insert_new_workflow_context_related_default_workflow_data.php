<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class InsertNewWorkflowContextRelatedDefaultWorkflowData extends Migration
{
    // /**
    //  * Run the migrations.
    //  *
    //  * @return void
    //  */
    public function up()
    {
        $contextData = array(
            [
                'id'=> 4,
                'contextName' => 'Apply Short Leave',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ]
        );

        $actionData = array(
            [
                'id'=> 15,
                'actionName' => 'Apply Short Leave Create',
                'label' => 'Create',
                'isPrimary' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 16,
                'actionName' => 'Apply Short Leave Approve',
                'label' => 'Approve',
                'isPrimary' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 17,
                'actionName' => 'Apply Short Leave Reject',
                'label' => 'Reject',
                'isPrimary' => false,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 18,
                'actionName' => 'Apply Short Leave Cancel',
                'label' => 'Cancel Request',
                'isPrimary' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 19,
                'actionName' => 'Apply Short Leave Supervisor Cancel',
                'label' => 'Cancel Request',
                'isPrimary' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ]
            

        );


        $workflowDefineData = array(
            [
                'id'=> 4,
                'workflowName' => 'Apply Short Leave',
                'contextId' => 4,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'sucessStates' => json_encode([2]),
                'failureStates' => json_encode([3, 4]),
                'isReadOnly' => true
            ]
            
        );

        $transitionData = array(
            [
                'id'=> 11,
                'workflowId' => 4,
                'actionId' => 16,
                'priorStateId' => 1,
                'postStateId' => 2,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'permittedRoles' => json_encode([3]),
                'permissionType' => 'ROLE_BASE',
                'isReadOnly' => true
            ],
            [
                'id'=> 12,
                'workflowId' => 4,
                'actionId' => 17,
                'priorStateId' => 1,
                'postStateId' => 3,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'permittedRoles' => json_encode([3]),
                'permissionType' => 'ROLE_BASE',
                'isReadOnly' => true
            ],
            [
                'id'=> 13,
                'workflowId' => 4,
                'actionId' => 18,
                'priorStateId' => 1,
                'postStateId' => 4,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'permittedRoles' => json_encode([2]),
                'permissionType' => 'ROLE_BASE',
                'isReadOnly' => true
            ],
            [
                'id'=> 14,
                'workflowId' => 4,
                'actionId' => 19,
                'priorStateId' => 2,
                'postStateId' => 4,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'permittedRoles' => json_encode([3]),
                'permissionType' => 'ROLE_BASE',
                'isReadOnly' => true
            ]
        );


        try {

            //insert short leave context
            foreach ($contextData as $contextKey => $context) {
                $context = (array) $context;
               
                $record = DB::table('workflowContext')->where('id', $context['id'])->first();
    
                if ($record) {
                    return('workflow context id does exist');
                }
    
                DB::table('workflowContext')->insert($context);
            }


            //insert short leave related actions
            foreach ($actionData as $actionKey => $action) {
                $action = (array) $action;
               
                $record = DB::table('workflowAction')->where('id', $action['id'])->first();
    
                if ($record) {
                    return('workflow action id does exist');
                }
    
                DB::table('workflowAction')->insert($action);
            }

            //insert short leave related actions
            foreach ($workflowDefineData as $workflowDefineKey => $workflowDefine) {
                $workflowDefine = (array) $workflowDefine;
               
                $record = DB::table('workflowDefine')->where('id', $workflowDefine['id'])->first();
    
                if ($record) {
                    return('workflow define id does exist');
                }
    
                DB::table('workflowDefine')->insert($workflowDefine);
            }

            //insert short leave related actions
            foreach ($transitionData as $transitionKey => $transition) {
                $transition = (array) $transition;
               
                $record = DB::table('workflowStateTransitions')->where('id', $transition['id'])->first();
    
                if ($record) {
                    return('workflow state transition id does exist');
                }
    
                DB::table('workflowStateTransitions')->insert($transition);
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
                'id'=> 4,
                'contextName' => 'Apply Short Leave',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ]
        );

        $actionData = array(
            [
                'id'=> 15,
                'actionName' => 'Apply Short Leave Create',
                'label' => 'Create',
                'isPrimary' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 16,
                'actionName' => 'Apply Short Leave Approve',
                'label' => 'Approve',
                'isPrimary' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 17,
                'actionName' => 'Apply Short Leave Reject',
                'label' => 'Reject',
                'isPrimary' => false,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 18,
                'actionName' => 'Apply Short Leave Cancel',
                'label' => 'Cancel Request',
                'isPrimary' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 19,
                'actionName' => 'Apply Short Leave Supervisor Cancel',
                'label' => 'Cancel Request',
                'isPrimary' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ]
            

        );


        $workflowDefineData = array(
            [
                'id'=> 4,
                'workflowName' => 'Apply Short Leave',
                'contextId' => 4,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'sucessStates' => json_encode([2]),
                'failureStates' => json_encode([3, 4]),
                'isReadOnly' => true
            ]
            
        );

        $transitionData = array(
            [
                'id'=> 11,
                'workflowId' => 4,
                'actionId' => 16,
                'priorStateId' => 1,
                'postStateId' => 2,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'permittedRoles' => json_encode([3]),
                'permissionType' => 'ROLE_BASE',
                'isReadOnly' => true
            ],
            [
                'id'=> 12,
                'workflowId' => 4,
                'actionId' => 17,
                'priorStateId' => 1,
                'postStateId' => 3,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'permittedRoles' => json_encode([3]),
                'permissionType' => 'ROLE_BASE',
                'isReadOnly' => true
            ],
            [
                'id'=> 13,
                'workflowId' => 4,
                'actionId' => 18,
                'priorStateId' => 1,
                'postStateId' => 4,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'permittedRoles' => json_encode([2]),
                'permissionType' => 'ROLE_BASE',
                'isReadOnly' => true
            ],
            [
                'id'=> 14,
                'workflowId' => 4,
                'actionId' => 19,
                'priorStateId' => 2,
                'postStateId' => 4,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'permittedRoles' => json_encode([3]),
                'permissionType' => 'ROLE_BASE',
                'isReadOnly' => true
            ]
        );


        try {

            //insert short leave context
            foreach ($contextData as $contextKey => $context) {
                $context = (array) $context;
               
                $record = DB::table('workflowContext')->where('id', $context['id'])->first();
    
                if ($record) {
                    DB::table('workflowContext')->where('id', $context['id'])->delete();
                }    
            }


            //insert short leave related actions
            foreach ($actionData as $actionKey => $action) {
                $action = (array) $action;
               
                $record = DB::table('workflowAction')->where('id', $action['id'])->first();
    
                if ($record) {
                    DB::table('workflowAction')->where('id', $action['id'])->delete();
                }    
            }

            //insert short leave related actions
            foreach ($workflowDefineData as $workflowDefineKey => $workflowDefine) {
                $workflowDefine = (array) $workflowDefine;
               
                $record = DB::table('workflowDefine')->where('id', $workflowDefine['id'])->first();
    
                if ($record) {
                    DB::table('workflowDefine')->where('id', $workflowDefine['id'])->delete();
                }
    
            }

            //insert short leave related actions
            foreach ($transitionData as $transitionKey => $transition) {
                $transition = (array) $transition;
               
                $record = DB::table('workflowStateTransitions')->where('id', $transition['id'])->first();
    
                if ($record) {
                    DB::table('workflowStateTransitions')->where('id', $transition['id'])->delete();
                }
    
            }

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
