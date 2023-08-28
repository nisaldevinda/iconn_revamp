<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewDefaultCreateActionsForWorkflowActions extends Migration
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
                'id'=> 12,
                'actionName' => 'Profile Update Request Create',
                'label' => 'Create',
                'isPrimary' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 13,
                'actionName' => 'Apply Leave Request Create',
                'label' => 'Create',
                'isPrimary' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 14,
                'actionName' => 'Time Change Request Create',
                'label' => 'Create',
                'isPrimary' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ]

        );
        
        try {

            foreach ($dataSet as $key => $data) {
               
                $record = DB::table('workflowAction')->where('id', $data['id'])->first();
    
                if ($record) {
                    return('workflow action does exist');
                }
    
                DB::table('workflowAction')->insert($data);
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
                'id'=> 12,
                'actionName' => 'Profile Update Request Create',
                'label' => 'Create',
                'isPrimary' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 13,
                'actionName' => 'Apply Leave Request Create',
                'label' => 'Create',
                'isPrimary' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 14,
                'actionName' => 'Time Change Request Create',
                'label' => 'Create',
                'isPrimary' => true,
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ]

        );
        
        try {

            foreach ($dataSet as $key => $data) {
               
                $record = DB::table('workflowAction')->where('id', $data['id'])->first();
    
                if (empty($record)) {
                    return('Workflow action does not exist');
                }
    
                $affectedRows  = DB::table('workflowAction')->where('id', $data['id'])->delete();
            }

            return ($affectedRows) ? true : false;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
