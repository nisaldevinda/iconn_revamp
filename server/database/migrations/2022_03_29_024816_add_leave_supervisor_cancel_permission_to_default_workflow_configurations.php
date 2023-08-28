<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLeaveSupervisorCancelPermissionToDefaultWorkflowConfigurations extends Migration
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
                'id'=> 4,
                'roleId' => 2,
                'actionId' => json_encode([7]),
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ]

        );

        try {

            //update manager permisson for leave

            $managerPermission = DB::table('workflowPermission')->where('id', 2)->first();
            if (empty($managerPermission)) {
                return('Workflow permission does not exist');
            }

            $managerPermission = (array) $managerPermission ;
            $managerPermission['actionId'] = json_encode([5,6,11]);

            $updateData = DB::table('workflowPermission')->upsert($managerPermission, $managerPermission['id']);

            foreach ($dataSet as $key => $data) {
                
                $record = DB::table('workflowPermission')->where('id', $data['id'])->first();
                
                if (!empty($record)) {
                    return('Workflow permission does exist');
                }

                $res = DB::table('workflowPermission')->insert($data);
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
                'id'=> 4,
                'roleId' => 2,
                'actionId' => json_encode([7]),
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ]

        );

        try {

            //update manager permisson for leave

            $managerPermission = DB::table('workflowPermission')->where('id', 2)->first();
            if (empty($record)) {
                return('Workflow permission does not exist');
            }

            $managerPermission = (array) $managerPermission ;
            $managerPermission['actionId'] = json_encode([5,6]);

            $updateData = DB::table('workflowPermission')->upsert($managerPermission, $managerPermission['id']);

            foreach ($dataSet as $key => $data) {
                
                $record = DB::table('workflowPermission')->where('id', $data['id'])->first();

                if (empty($record)) {
                    return('Workflow permission does not exist');
                }
    
                $affectedRows  = DB::table('workflowPermission')->where('id', $data['id'])->delete();
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
