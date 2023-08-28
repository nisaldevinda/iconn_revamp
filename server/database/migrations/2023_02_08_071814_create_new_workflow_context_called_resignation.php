<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewWorkflowContextCalledResignation extends Migration
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
                'id'=> 7,
                'contextName' => 'Resignation',
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
                'id'=> 28,
                'actionName' => 'Resignation Create',
                'label' => 'Create',
                'isPrimary' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 29,
                'actionName' => 'Resignation Approve',
                'label' => 'Approve',
                'isPrimary' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 30,
                'actionName' => 'Resignation Reject',
                'label' => 'Reject',
                'isPrimary' => false,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 31,
                'actionName' => 'Resignation Cancel',
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
                'id'=> 7,
                'workflowName' => 'Resignation',
                'contextId' => 7,
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
                'id'=> 21,
                'workflowId' => 7,
                'actionId' => 29,
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
                'id'=> 22,
                'workflowId' => 7,
                'actionId' => 30,
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
                'id'=> 23,
                'workflowId' => 7,
                'actionId' => 31,
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
            
            //insert resignation context
            foreach ($contextData as $contextKey => $context) {
                $context = (array) $context;
               
                $record = DB::table('workflowContext')->where('id', $context['id'])->first();
                
                if (is_null($record)) {
                    DB::table('workflowContext')->insert($context);
                }
    
            }


            //insert resignation related actions
            foreach ($actionData as $actionKey => $action) {
                $action = (array) $action;
               
                $record = DB::table('workflowAction')->where('id', $action['id'])->first();
    
                if (is_null($record)) {
                    DB::table('workflowAction')->insert($action);
                }
    
            }

            //insert resignation related workflow
            foreach ($workflowDefineData as $workflowDefineKey => $workflowDefine) {
                $workflowDefine = (array) $workflowDefine;
               
                $record = DB::table('workflowDefine')->where('id', $workflowDefine['id'])->first();
    
                if (is_null($record)) {
                    DB::table('workflowDefine')->insert($workflowDefine);
                }
    
                
            }

            //insert resignation related workflow transitions
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
                    'id'=> 7,
                    'contextName' => 'Resignation',
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
                    'id'=> 28,
                    'actionName' => 'Resignation Create',
                    'label' => 'Create',
                    'isPrimary' => true,
                    'isDelete' => false,
                    'createdBy' => null,
                    'updatedBy' => null,
                    'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                    'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                ],
                [
                    'id'=> 29,
                    'actionName' => 'Resignation Approve',
                    'label' => 'Approve',
                    'isPrimary' => true,
                    'isDelete' => false,
                    'createdBy' => null,
                    'updatedBy' => null,
                    'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                    'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                ],
                [
                    'id'=> 30,
                    'actionName' => 'Resignation Reject',
                    'label' => 'Reject',
                    'isPrimary' => false,
                    'isDelete' => false,
                    'createdBy' => null,
                    'updatedBy' => null,
                    'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                    'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                ],
                [
                    'id'=> 31,
                    'actionName' => 'Resignation Cancel',
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
                    'id'=> 7,
                    'workflowName' => 'Resignation',
                    'contextId' => 7,
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
                    'id'=> 21,
                    'workflowId' => 7,
                    'actionId' => 29,
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
                    'id'=> 22,
                    'workflowId' => 7,
                    'actionId' => 30,
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
                    'id'=> 23,
                    'workflowId' => 7,
                    'actionId' => 31,
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
    
            //remove resignation workflow context
            foreach ($contextData as $contextKey => $context) {
                $context = (array) $context;
               
                $record = DB::table('workflowContext')->where('id', $context['id'])->first();
    
                if (!is_null($record)) {
                    DB::table('workflowContext')->where('id', $context['id'])->delete();
                }    
            }


            //remove resignation request actions
            foreach ($actionData as $actionKey => $action) {
                $action = (array) $action;
               
                $record = DB::table('workflowAction')->where('id', $action['id'])->first();
    
                if (!is_null($record)) {
                    DB::table('workflowAction')->where('id', $action['id'])->delete();
                }    
            }

            //remove resignation request default workflow
            foreach ($workflowDefineData as $workflowDefineKey => $workflowDefine) {
                $workflowDefine = (array) $workflowDefine;
               
                $record = DB::table('workflowDefine')->where('id', $workflowDefine['id'])->first();
    
                if (!is_null($record)) {
                    DB::table('workflowDefine')->where('id', $workflowDefine['id'])->delete();
                }
    
            }

            //remove resignation request default waotkflow reated transitions
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
