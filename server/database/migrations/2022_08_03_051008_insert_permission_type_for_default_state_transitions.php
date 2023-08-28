<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertPermissionTypeForDefaultStateTransitions extends Migration
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
                'permissionType' => 'ROLE_BASE',
            ],
            [
                'id'=> 2,
                'permissionType' => 'ROLE_BASE',
            ],
            [
                'id'=> 3,
                'permissionType' => 'ROLE_BASE',
            ],
            [
                'id'=> 4,
                'permissionType' => 'ROLE_BASE',
            ],
            [
                'id'=> 5,
                'permissionType' => 'ROLE_BASE',
            ],
            [
                'id'=> 6,
                'permissionType' => 'ROLE_BASE',
            ],
            [
                'id'=> 7,
                'permissionType' => 'ROLE_BASE',
            ],
            [
                'id'=> 8,
                'permissionType' => 'ROLE_BASE',
            ],
            [
                'id'=> 9,
                'permissionType' => 'ROLE_BASE',
            ],
            [
                'id'=> 10,
                'permissionType' => 'ROLE_BASE',
            ]
        );

        try {

            foreach ($dataSet as $key => $data) {
               
                $data = (array) $data;
    
                DB::table('workflowStateTransitions')->where('workflowStateTransitions.id', $data['id'])->update($data);
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
                'permissionType' => null,
            ],
            [
                'id'=> 2,
                'permissionType' => null,
            ],
            [
                'id'=> 3,
                'permissionType' => null,
            ],
            [
                'id'=> 4,
                'permissionType' => null,
            ],
            [
                'id'=> 5,
                'permissionType' => null,
            ],
            [
                'id'=> 6,
                'permissionType' => null,
            ],
            [
                'id'=> 7,
                'permissionType' => null,
            ],
            [
                'id'=> 8,
                'permissionType' => null,
            ],
            [
                'id'=> 9,
                'permissionType' => null,
            ],
            [
                'id'=> 10,
                'permissionType' => null,
            ]
        );

        try {

            foreach ($dataSet as $key => $data) {
               
                $data = (array) $data;
    
                DB::table('workflowStateTransitions')->where('workflowStateTransitions.id', $data['id'])->update($data);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
