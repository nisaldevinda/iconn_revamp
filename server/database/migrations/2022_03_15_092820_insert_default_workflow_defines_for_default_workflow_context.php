<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertDefaultWorkflowDefinesForDefaultWorkflowContext extends Migration
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
                'workflowName' => 'Profile Update',
                'contextId' => 1,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'sucessStates' => json_encode([2]),
                'failureStates' => json_encode([3, 4])
            ],
            [
                'id'=> 2,
                'workflowName' => 'Apply Leave',
                'contextId' => 2,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'sucessStates' => json_encode([2]),
                'failureStates' => json_encode([3, 4])
            ],
            [
                'id'=> 3,
                'workflowName' => 'Attendence Time Change',
                'contextId' => 3,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'sucessStates' => json_encode([2]),
                'failureStates' => json_encode([3, 4])
            ]

        );

        try {

            foreach ($dataSet as $key => $data) {
               
                $record = DB::table('workflowDefine')->where('id', $data['id'])->first();
    
                if ($record) {
                    return('workflow define does exist');
                }
    
                DB::table('workflowDefine')->insert($data);
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
                'workflowName' => 'Profile Update',
                'contextId' => 1,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'sucessStates' => json_encode([2]),
                'failureStates' => json_encode([3, 4])
            ],
            [
                'id'=> 2,
                'workflowName' => 'Apply Leave',
                'contextId' => 2,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'sucessStates' => json_encode([2]),
                'failureStates' => json_encode([3, 4])
            ],
            [
                'id'=> 3,
                'workflowName' => 'Attendence Time Change',
                'contextId' => 3,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'sucessStates' => json_encode([2]),
                'failureStates' => json_encode([3, 4])
            ]

        );
        try {

            foreach ($dataSet as $key => $data) {
               
                $record = DB::table('workflowDefine')->where('id', $data['id'])->first();
    
                if (empty($record)) {
                    return('Workflow define does not exist');
                }
    
                $affectedRows  = DB::table('workflowDefine')->where('id', $data['id'])->delete();
            }

            return ($affectedRows) ? true : false;
        } catch (\Throwable $th) {
            throw $th;
        }

    }
}
