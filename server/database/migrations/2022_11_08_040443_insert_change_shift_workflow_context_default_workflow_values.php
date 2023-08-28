<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


class InsertChangeShiftWorkflowContextDefaultWorkflowValues extends Migration
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
                'id'=> 5,
                'contextName' => 'Shift Change',
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
                'id'=> 20,
                'actionName' => 'Shift Change Create',
                'label' => 'Create',
                'isPrimary' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 21,
                'actionName' => 'Shift Change Approve',
                'label' => 'Approve',
                'isPrimary' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 22,
                'actionName' => 'Shift Change Reject',
                'label' => 'Reject',
                'isPrimary' => false,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 23,
                'actionName' => 'Shift Change Cancel',
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
                'id'=> 5,
                'workflowName' => 'Shift Change',
                'contextId' => 5,
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
                'id'=> 15,
                'workflowId' => 5,
                'actionId' => 21,
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
                'id'=> 16,
                'workflowId' => 5,
                'actionId' => 22,
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
                'id'=> 17,
                'workflowId' => 5,
                'actionId' => 23,
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
            

            //insert short leave context
            foreach ($contextData as $contextKey => $context) {
                $context = (array) $context;
               
                $record = DB::table('workflowContext')->where('id', $context['id'])->first();
                
                if (is_null($record)) {
                    DB::table('workflowContext')->insert($context);
                }
    
            }


            //insert short leave related actions
            foreach ($actionData as $actionKey => $action) {
                $action = (array) $action;
               
                $record = DB::table('workflowAction')->where('id', $action['id'])->first();
    
                if (is_null($record)) {
                    DB::table('workflowAction')->insert($action);
                }
    
            }

            //insert short leave related actions
            foreach ($workflowDefineData as $workflowDefineKey => $workflowDefine) {
                $workflowDefine = (array) $workflowDefine;
               
                $record = DB::table('workflowDefine')->where('id', $workflowDefine['id'])->first();
    
                if (is_null($record)) {
                    DB::table('workflowDefine')->insert($workflowDefine);
                }
    
                
            }

            //insert short leave related actions
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
                    'id'=> 5,
                    'contextName' => 'Shift Change',
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
                    'id'=> 20,
                    'actionName' => 'Shift Change Create',
                    'label' => 'Create',
                    'isPrimary' => true,
                    'isDelete' => false,
                    'createdBy' => null,
                    'updatedBy' => null,
                    'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                    'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                ],
                [
                    'id'=> 21,
                    'actionName' => 'Shift Change Approve',
                    'label' => 'Approve',
                    'isPrimary' => true,
                    'isDelete' => false,
                    'createdBy' => null,
                    'updatedBy' => null,
                    'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                    'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                ],
                [
                    'id'=> 22,
                    'actionName' => 'Shift Change Reject',
                    'label' => 'Reject',
                    'isPrimary' => false,
                    'isDelete' => false,
                    'createdBy' => null,
                    'updatedBy' => null,
                    'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                    'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                ],
                [
                    'id'=> 23,
                    'actionName' => 'Shift Change Cancel',
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
                    'id'=> 5,
                    'workflowName' => 'Shift Change',
                    'contextId' => 5,
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
                    'id'=> 15,
                    'workflowId' => 5,
                    'actionId' => 21,
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
                    'id'=> 16,
                    'workflowId' => 5,
                    'actionId' => 22,
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
                    'id'=> 17,
                    'workflowId' => 5,
                    'actionId' => 23,
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

            //insert short leave context
            foreach ($contextData as $contextKey => $context) {
                $context = (array) $context;
               
                $record = DB::table('workflowContext')->where('id', $context['id'])->first();
    
                if (!is_null($record)) {
                    DB::table('workflowContext')->where('id', $context['id'])->delete();
                }    
            }


            //insert short leave related actions
            foreach ($actionData as $actionKey => $action) {
                $action = (array) $action;
               
                $record = DB::table('workflowAction')->where('id', $action['id'])->first();
    
                if (!is_null($record)) {
                    DB::table('workflowAction')->where('id', $action['id'])->delete();
                }    
            }

            //insert short leave related actions
            foreach ($workflowDefineData as $workflowDefineKey => $workflowDefine) {
                $workflowDefine = (array) $workflowDefine;
               
                $record = DB::table('workflowDefine')->where('id', $workflowDefine['id'])->first();
    
                if (!is_null($record)) {
                    DB::table('workflowDefine')->where('id', $workflowDefine['id'])->delete();
                }
    
            }

            //insert short leave related actions
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
