<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertDocumentManagerData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $folderHierarchyData = array(
            [
                'id' => 1,
                'folderName' => 'Company Folder',
                'type' => 'COMPANY',
                'hierarchyId' => 0,
                'parentId' => 0,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'fieldName' => 'companyFolder',
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id' => 2,
                'folderName' => 'Employee Folder',
                'type' => 'EMPLOYEE',
                'hierarchyId' => 0,
                'parentId' => 0,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'fieldName' => 'employeeFolder',
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],[
                'id' => 3,
                'folderName' => 'Contracts',
                'type' => 'OTHER',
                'hierarchyId' => 1,
                'parentId' => 0,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'fieldName' => 'contracts',
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],[
                'id' => 4,
                'folderName' => 'Forms',
                'type' => 'OTHER',
                'hierarchyId' => 1,
                'parentId' => 1,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'fieldName' => 'forms',
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],[
                'id' => 5,
                'folderName' => 'Policies',
                'type' => 'OTHER',
                'hierarchyId' => 1,
                'parentId' => 1,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'fieldName' => 'policies',
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],[
                'id' => 6,
                'folderName' => 'Templates',
                'type' => 'OTHER',
                'hierarchyId' => 1,
                'parentId' => 1,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'fieldName' => 'templates',
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],[
                'id' => 7,
                'folderName' => 'Other',
                'type' => 'OTHER',
                'hierarchyId' => 1,
                'parentId' => 1,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'fieldName' => 'Other',
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],[
                'id' => 8,
                'folderName' => 'Personal',
                'type' => 'OTHER',
                'hierarchyId' => 1,
                'parentId' => 2,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'fieldName' => 'personal',
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],[
                'id' => 9,
                'folderName' => 'Forms',
                'type' => 'OTHER',
                'hierarchyId' => 1,
                'parentId' => 2,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'fieldName' => 'forms',
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],[
                'id' => 10,
                'folderName' => 'Contracts',
                'type' => 'OTHER',
                'hierarchyId' => 1,
                'parentId' => 2,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'fieldName' => 'contracts',
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],[
                'id' => 11,
                'folderName' => 'Other',
                'type' => 'OTHER',
                'hierarchyId' => 1,
                'parentId' => 2,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'fieldName' => 'other',
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],[
                'id' => 12,
                'folderName' => 'OTHER Folder',
                'type' => 'OTHER',
                'hierarchyId' => 1,
                'parentId' => 1,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'fieldName' => 'OTHERFolder1',
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ]
        );
        DB::table('folderHierarchy')->insert($folderHierarchyData);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('folderHierarchy')->where('id', [1,2,3,4,5,6,7,8,9,10,11,12])->delete();
    }
}
