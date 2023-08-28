<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertFolderHierarchyTableData extends Migration
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
                'name' => 'Company Folder',
                'type' => 'COMPANY',
                'slug' => 'company',
                'parentId' => 0,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id' => 2,
                'name' => 'Employee Folder',
                'type' => 'EMPLOYEE',
                'slug' => 'employee',
                'parentId' => 0,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],[
                'id' => 3,
                'name' => 'Contracts',
                'type' => 'OTHER',
                'slug' => 'company-contracts',
                'parentId' => 1,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],[
                'id' => 4,
                'name' => 'Forms',
                'type' => 'OTHER',
                'slug' => 'company-forms',
                'parentId' => 1,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],[
                'id' => 5,
                'name' => 'Policies',
                'type' => 'OTHER',
                'slug' => 'company-policies',
                'parentId' => 1,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],[
                'id' => 6,
                'name' => 'Templates',
                'type' => 'OTHER',
                'slug' => 'company-templates',
                'parentId' => 1,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],[
                'id' => 7,
                'name' => 'Certificates',
                'type' => 'OTHER',
                'slug' => 'employee-certificates',
                'parentId' => 2,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],[
                'id' => 8,
                'name' => 'Personal',
                'type' => 'OTHER',
                'slug' => 'employee-personal',
                'parentId' => 2,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],[
                'id' => 9,
                'name' => 'Forms',
                'type' => 'OTHER',
                'slug' => 'employee-forms',
                'parentId' => 2,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],[
                'id' => 10,
                'name' => 'Contracts',
                'type' => 'OTHER',
                'slug' => 'employee-contracts',
                'parentId' => 2,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],[
                'id' => 11,
                'name' => 'Other',
                'type' => 'OTHER',
                'slug' => 'employee-other',
                'parentId' => 2,
                'isDelete' => FALSE,
                'createdBy' => 1,
                'updatedBy' => 1,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ]
        );
        DB::table('documentManagerFolder')->insert($folderHierarchyData);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('documentManagerFolder')->where('id', [1,2,3,4,5,6,7,8,9,10,11,12])->delete();
    }
}
