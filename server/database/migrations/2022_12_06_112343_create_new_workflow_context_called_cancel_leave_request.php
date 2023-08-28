<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateNewWorkflowContextCalledCancelLeaveRequest extends Migration
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
                'id'=> 6,
                'contextName' => 'Cancel Leave',
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
                'id'=> 24,
                'actionName' => 'Cancel Leave Create',
                'label' => 'Create',
                'isPrimary' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 25,
                'actionName' => 'Cancel Leave Approve',
                'label' => 'Approve',
                'isPrimary' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 26,
                'actionName' => 'Cancel Leave Reject',
                'label' => 'Reject',
                'isPrimary' => false,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 27,
                'actionName' => 'Cancel Leave Cancel',
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
                'id'=> 6,
                'workflowName' => 'Cancel Leave',
                'contextId' => 6,
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
                'id'=> 18,
                'workflowId' => 6,
                'actionId' => 25,
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
                'id'=> 19,
                'workflowId' => 6,
                'actionId' => 26,
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
                'id'=> 20,
                'workflowId' => 6,
                'actionId' => 27,
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
            ]
        );

        
        
        try {
            

            //insert cancel leave context
            foreach ($contextData as $contextKey => $context) {
                $context = (array) $context;
               
                $record = DB::table('workflowContext')->where('id', $context['id'])->first();
                
                if (is_null($record)) {
                    DB::table('workflowContext')->insert($context);
                }
    
            }


            //insert cancel leave related actions
            foreach ($actionData as $actionKey => $action) {
                $action = (array) $action;
               
                $record = DB::table('workflowAction')->where('id', $action['id'])->first();
    
                if (is_null($record)) {
                    DB::table('workflowAction')->insert($action);
                }
    
            }

            //insert cancel leave related workflow
            foreach ($workflowDefineData as $workflowDefineKey => $workflowDefine) {
                $workflowDefine = (array) $workflowDefine;
               
                $record = DB::table('workflowDefine')->where('id', $workflowDefine['id'])->first();
    
                if (is_null($record)) {
                    DB::table('workflowDefine')->insert($workflowDefine);
                }
    
                
            }

            //insert cancel leave related workflow transitions
            foreach ($transitionData as $transitionKey => $transition) {
                $transition = (array) $transition;
               
                $record = DB::table('workflowStateTransitions')->where('id', $transition['id'])->first();
    
                if (is_null($record)) {
                    DB::table('workflowStateTransitions')->insert($transition);
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
        try {

            $contextData = array(
                [
                    'id'=> 6,
                    'contextName' => 'Cancel Leave',
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
                    'id'=> 24,
                    'actionName' => 'Cancel Leave Create',
                    'label' => 'Create',
                    'isPrimary' => true,
                    'isDelete' => false,
                    'createdBy' => null,
                    'updatedBy' => null,
                    'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                    'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                ],
                [
                    'id'=> 25,
                    'actionName' => 'Cancel Leave Approve',
                    'label' => 'Approve',
                    'isPrimary' => true,
                    'isDelete' => false,
                    'createdBy' => null,
                    'updatedBy' => null,
                    'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                    'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                ],
                [
                    'id'=> 26,
                    'actionName' => 'Cancel Leave Reject',
                    'label' => 'Reject',
                    'isPrimary' => false,
                    'isDelete' => false,
                    'createdBy' => null,
                    'updatedBy' => null,
                    'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                    'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                ],
                [
                    'id'=> 27,
                    'actionName' => 'Cancel Leave Cancel',
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
                    'id'=> 6,
                    'workflowName' => 'Cancel Leave',
                    'contextId' => 6,
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
                    'id'=> 18,
                    'workflowId' => 6,
                    'actionId' => 25,
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
                    'id'=> 19,
                    'workflowId' => 6,
                    'actionId' => 26,
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
                    'id'=> 20,
                    'workflowId' => 6,
                    'actionId' => 27,
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
                ]
            );
    
            //remove cancel leave context
            foreach ($contextData as $contextKey => $context) {
                $context = (array) $context;
               
                $record = DB::table('workflowContext')->where('id', $context['id'])->first();
    
                if (!is_null($record)) {
                    DB::table('workflowContext')->where('id', $context['id'])->delete();
                }    
            }


            //remove cancel leave related actions
            foreach ($actionData as $actionKey => $action) {
                $action = (array) $action;
               
                $record = DB::table('workflowAction')->where('id', $action['id'])->first();
    
                if (!is_null($record)) {
                    DB::table('workflowAction')->where('id', $action['id'])->delete();
                }    
            }

            //remove cancel leave related default workflow
            foreach ($workflowDefineData as $workflowDefineKey => $workflowDefine) {
                $workflowDefine = (array) $workflowDefine;
               
                $record = DB::table('workflowDefine')->where('id', $workflowDefine['id'])->first();
    
                if (!is_null($record)) {
                    DB::table('workflowDefine')->where('id', $workflowDefine['id'])->delete();
                }
    
            }

            //remove cancel leave related default waotkflow reated transitions
            foreach ($transitionData as $transitionKey => $transition) {
                $transition = (array) $transition;
               
                $record = DB::table('workflowStateTransitions')->where('id', $transition['id'])->first();
    
                if (!is_null($record)) {
                    DB::table('workflowStateTransitions')->where('id', $transition['id'])->delete();
                }
    
            }

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
