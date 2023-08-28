<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertDefaultWorkflowActionsToWorkflowActionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $actionData = array(
            [
                'id'=> 1,
                'actionName' => 'Create',
                'label' => 'Create',
                'description'=> 'Create Request',
                'isSuccessAction' => true,
                'isDelete' => false,
                'isReadOnly' => true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 2,
                'actionName' => 'Approve',
                'label' => 'Approve',
                'description'=> 'Approve Request',
                'isSuccessAction' => true,
                'isDelete' => false,
                'isReadOnly' => true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 3,
                'actionName' => 'Reject',
                'label' => 'Reject',
                'description'=> 'Reject Request',
                'isSuccessAction' => false,
                'isDelete' => false,
                'isReadOnly' => true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 4,
                'actionName' => 'Cancel',
                'label' => 'Cancel Request',
                'description'=> 'Reject Request',
                'isSuccessAction' => false,
                'isDelete' => false,
                'isReadOnly' => true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
        );

        try {
            foreach ($actionData as $actionKey => $action) {
                $action = (array) $action;
               
                $record = DB::table('workflowAction')->where('id', $action['id'])->first();
    
                if (is_null($record)) {
                    DB::table('workflowAction')->insert($action);
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
        $actionData = array(
            [
                'id'=> 1,
                'actionName' => 'Create',
                'label' => 'Create',
                'description'=> 'Create Request',
                'isSuccessAction' => true,
                'isDelete' => false,
                'isReadOnly' => true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 2,
                'actionName' => 'Approve',
                'label' => 'Approve',
                'description'=> 'Approve Request',
                'isSuccessAction' => true,
                'isDelete' => false,
                'isReadOnly' => true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 3,
                'actionName' => 'Reject',
                'label' => 'Reject',
                'description'=> 'Reject Request',
                'isSuccessAction' => false,
                'isDelete' => false,
                'isReadOnly' => true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 4,
                'actionName' => 'Cancel',
                'label' => 'Cancel Request',
                'description'=> 'Reject Request',
                'isSuccessAction' => false,
                'isDelete' => false,
                'isReadOnly' => true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
        );

        try {
            foreach ($actionData as $actionKey => $action) {
                $action = (array) $action;
               
                $record = DB::table('workflowAction')->where('id', $action['id'])->first();
    
                if (!is_null($record)) {
                    DB::table('workflowAction')->where('id', $action['id'])->delete();
                }
    
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
