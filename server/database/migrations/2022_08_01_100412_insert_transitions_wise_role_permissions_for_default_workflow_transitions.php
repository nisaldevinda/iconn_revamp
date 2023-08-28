<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertTransitionsWiseRolePermissionsForDefaultWorkflowTransitions extends Migration
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
                'permittedRoles' => [1],
            ],
            [
                'id'=> 2,
                'permittedRoles' => [1],
            ],
            [
                'id'=> 3,
                'permittedRoles' => [2],
            ],
            [
                'id'=> 4,
                'permittedRoles' => [3],
            ],
            [
                'id'=> 5,
                'permittedRoles' => [3],

            ],
            [
                'id'=> 6,
                'permittedRoles' => [2],
            ],
            [
                'id'=> 7,
                'permittedRoles' => [3],
            ],
            [
                'id'=> 8,
                'permittedRoles' => [3],
            ],
            [
                'id'=> 9,
                'permittedRoles' => [2],
            ],
            [
                'id'=> 10,
                'permittedRoles' => [3],
            ]
        );

        try {

            foreach ($dataSet as $key => $data) {
               
                $data = (array) $data;

                $data['permittedRoles'] = json_encode($data['permittedRoles']);
    
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
                'permittedRoles' => null,
            ],
            [
                'id'=> 2,
                'permittedRoles' => null,
            ],
            [
                'id'=> 3,
                'permittedRoles' => null,
            ],
            [
                'id'=> 4,
                'permittedRoles' => null,
            ],
            [
                'id'=> 5,
                'permittedRoles' => null,

            ],
            [
                'id'=> 6,
                'permittedRoles' => null,
            ],
            [
                'id'=> 7,
                'permittedRoles' => null,
            ],
            [
                'id'=> 8,
                'permittedRoles' => null,
            ],
            [
                'id'=> 9,
                'permittedRoles' => null,
            ],
            [
                'id'=> 10,
                'permittedRoles' => null,
            ]
        );

        try {

            foreach ($dataSet as $key => $data) {
               
                $data = (array) $data;

                $data['permittedRoles'] = $data['permittedRoles'];
    
                DB::table('workflowStateTransitions')->where('workflowStateTransitions.id', $data['id'])->update($data);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
