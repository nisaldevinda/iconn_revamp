<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertDefaultWorkflowStateTransitionsForDefaultActions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $dataSet = array(
            [
                'id'=> 1,
                'workflowId' => 1,
                'actionId' => 2,
                'priorStateId' => 1,
                'postStateId' => 2,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP')
            ],
            [
                'id'=> 2,
                'workflowId' => 1,
                'actionId' => 3,
                'priorStateId' => 1,
                'postStateId' => 3,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP')
            ],
            [
                'id'=> 3,
                'workflowId' => 1,
                'actionId' => 4,
                'priorStateId' => 1,
                'postStateId' => 4,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP')
            ],
            [
                'id'=> 4,
                'workflowId' => 2,
                'actionId' => 5,
                'priorStateId' => 1,
                'postStateId' => 2,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP')
            ],
            [
                'id'=> 5,
                'workflowId' => 2,
                'actionId' => 6,
                'priorStateId' => 1,
                'postStateId' => 3,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP')
            ],
            [
                'id'=> 6,
                'workflowId' => 2,
                'actionId' => 7,
                'priorStateId' => 1,
                'postStateId' => 4,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP')
            ],
            [
                'id'=> 7,
                'workflowId' => 3,
                'actionId' => 8,
                'priorStateId' => 1,
                'postStateId' => 2,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP')
            ],
            [
                'id'=> 8,
                'workflowId' => 3,
                'actionId' => 9,
                'priorStateId' => 1,
                'postStateId' => 3,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP')
            ],
            [
                'id'=> 9,
                'workflowId' => 3,
                'actionId' => 10,
                'priorStateId' => 1,
                'postStateId' => 4,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP')
            ]


        );

        try {

            foreach ($dataSet as $key => $data) {
               
                $record = DB::table('workflowStateTransitions')->where('id', $data['id'])->first();
    
                if ($record) {
                    return('workflow state transition does exist');
                }
    
                DB::table('workflowStateTransitions')->insert($data);
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
        $dataSet = array(
            [
                'id'=> 1,
                'workflowId' => 1,
                'actionId' => 2,
                'priorStateId' => 1,
                'postStateId' => 2,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP')
            ],
            [
                'id'=> 2,
                'workflowId' => 1,
                'actionId' => 3,
                'priorStateId' => 1,
                'postStateId' => 3,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP')
            ],
            [
                'id'=> 3,
                'workflowId' => 2,
                'actionId' => 5,
                'priorStateId' => 1,
                'postStateId' => 2,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP')
            ],
            [
                'id'=> 4,
                'workflowId' => 2,
                'actionId' => 6,
                'priorStateId' => 1,
                'postStateId' => 3,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP')
            ],
            [
                'id'=> 5,
                'workflowId' => 3,
                'actionId' => 8,
                'priorStateId' => 1,
                'postStateId' => 2,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP')
            ],
            [
                'id'=> 6,
                'workflowId' => 3,
                'actionId' => 9,
                'priorStateId' => 1,
                'postStateId' => 3,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP')
            ]


        );
        try {

            foreach ($dataSet as $key => $data) {
               
                $record = DB::table('workflowStateTransitions')->where('id', $data['id'])->first();
    
                if (empty($record)) {
                    return('Workflow state transition not exist');
                }
    
                $affectedRows  = DB::table('workflowStateTransitions')->where('id', $data['id'])->delete();
            }

            return ($affectedRows) ? true : false;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
