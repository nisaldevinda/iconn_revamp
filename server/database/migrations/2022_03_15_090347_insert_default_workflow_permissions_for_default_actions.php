<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertDefaultWorkflowPermissionsForDefaultActions extends Migration
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
                'roleId' => 1,
                'actionId' => json_encode([2,3]),
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 2,
                'roleId' => 3,
                'actionId' => json_encode([5,6]),
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 3,
                'roleId' => 3,
                'actionId' => json_encode([8,9]),
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ]

        );

        try {

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
                'id'=> 1,
                'roleId' => 1,
                'actionId' => json_encode([2,3]),
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 2,
                'roleId' => 1,
                'actionId' => json_encode([5,6]),
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=> 3,
                'roleId' => 3,
                'actionId' => json_encode([8,9]),
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ]

        );

        try {

            foreach ($dataSet as $key => $data) {
               
                $record = DB::table('workflowPermission')->where('id', $data['id'])->first();
    
                if (empty($record)) {
                    return('Workflow permission does not exist');
                }
    
                $affectedRows  = DB::table('workflowPermission')->where('id', $data['id'])->delete();
            }

            return ($affectedRows) ? true : false;
        } catch (\Throwable $th) {
            throw $th;
        }


    }
}
