<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertDefaultWorkflowStates extends Migration
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
                'stateName' => 'Pending',
                'label' => 'Pending',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 2,
                'stateName' => 'Approved',
                'label' => 'Approved',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 3,
                'stateName' => 'Rejected',
                'label' => 'Rejected',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 4,
                'stateName' => 'Cancelled',
                'label' => 'Cancelled',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ]

        );

        try {

            foreach ($dataSet as $key => $data) {
               
                $record = DB::table('workflowState')->where('id', $data['id'])->first();
    
                if ($record) {
                    return('workflow state does exist');
                }
    
                DB::table('workflowState')->insert($data);
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
                'stateName' => 'Pending',
                'label' => 'Pending',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 2,
                'stateName' => 'Approved',
                'label' => 'Approved',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 3,
                'stateName' => 'Rejected',
                'label' => 'Rejected',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 4,
                'stateName' => 'Cancelled',
                'label' => 'Cancelled',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ]

        );
        try {

            foreach ($dataSet as $key => $data) {
               
                $record = DB::table('workflowState')->where('id', $data['id'])->first();
    
                if (empty($record)) {
                    return('Workflow state does not exist');
                }
    
                $affectedRows  = DB::table('workflowState')->where('id', $data['id'])->delete();
            }

            return ($affectedRows) ? true : false;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
