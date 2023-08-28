<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAnotherWorkflowStateTransitionForLeaveSupervisorCancel extends Migration
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
                'id'=> 10,
                'workflowId' => 2,
                'actionId' => 11,
                'priorStateId' => 2,
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
                'id'=> 10,
                'workflowId' => 2,
                'actionId' => 11,
                'priorStateId' => 2,
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
