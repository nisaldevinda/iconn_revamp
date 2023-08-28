<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertUserRoleData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $userRoleData = array(
            [
                'id'=>1,
                'title' => 'Global Admin',
                'type' => 'ADMIN',
                'isDirectAccess' => true,
                'isInDirectAccess' => true,
                'customCriteria' => json_encode('["*"]'),
                'permittedActions' => json_encode('["*"]'),
                'workflowManagementActions' => json_encode('["*"]'),
                'readableFields' => json_encode('["*"]'),
                'editableFields' => json_encode('["*"]'),
                'isEditable' => false,
                'isVisibility' => true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>2,
                'title' => 'No Action',
                'type' => 'OTHER',
                'isDirectAccess' => false,
                'isInDirectAccess' => false,
                'customCriteria' => json_encode('[""]'),
                'permittedActions' => json_encode('[""]'),
                'workflowManagementActions' => json_encode('[""]'),
                'readableFields' => json_encode('[""]'),
                'editableFields' => json_encode('[""]'),
                'isEditable' => false,
                'isVisibility' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ]
        );
        DB::table('userRole')->insert($userRoleData);
    }
    
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('userRole')->where('id', [1,2])->delete();
    }
}
