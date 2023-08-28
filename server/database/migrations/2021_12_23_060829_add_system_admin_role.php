<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSystemAdminRole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // drop `isGlobalAdmin` column
        if (Schema::hasColumn('user', 'isGlobalAdmin')) {
            Schema::table('user', function (Blueprint $table) {
                $table->dropColumn('isGlobalAdmin');
            });
        }


        // global admin
        DB::table('userRole')->where('id', 1)->update(
            [
                'customCriteria' => json_encode(['*']),
                'permittedActions' => json_encode(['*']),
                'workflowManagementActions' => json_encode(['*']),
                'fieldPermissions' => json_encode(['*'])
            ]
        );

        // system admin
        DB::table('userRole')->where('id', 2)->update(
            [
                'title' => 'System Admin',
                'type' => 'ADMIN',
                'isDirectAccess' => false,
                'isInDirectAccess' => false,
                'customCriteria' => json_encode([]),
                'permittedActions' => json_encode(["master-data-write", "master-data-read", "access-levels-read-write", "workflow-management-read-write"]),
                'workflowManagementActions' => json_encode(['*']),
                'fieldPermissions' => json_encode([]),
                'isEditable' => false,
                'isVisibility' => false
            ]
        );
        // assign global admin role to global admin
        DB::table('user')->where('id', 1)->update(['adminRoleId' => 1]);

        // create system admin user
        $systemAdmin =  [
            'email' => 'systemadmin@emageia.com',
            'emailVerified' => true,
            'firstName' => 'System',
            'middleName' => '',
            'lastName' => 'Admin',
            'nickname' => 'Sys Admin',
            'password' => '$2y$10$GzKpD.TTNWU88aEPuqvD4OsNyxnb1kyqclKh9JU4kKfGUhnWwwXBi', // password = 123
            'inactive' => false,
            'blocked' => false,
            'expired' => false,
            'multifactor' => '1dw',
            'adminRoleId' => 2,
            'createdBy' => null,
            'updatedBy' => null,
            'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
            'updatedAt' => DB::raw('CURRENT_TIMESTAMP')
        ];

        $result = DB::table('user')->where('id', 2)->first();
        if (is_null($result)) {
            // insert sys admin
            $systemAdmin['id'] = 2;
            DB::table('user')->insert($systemAdmin);
        } else {
            // update as sys admin
            DB::table('user')->where('id', 2)->update($systemAdmin);
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // create `isGlobalAdmin` column
        Schema::table('user', function (Blueprint $table) {
            $table->boolean('isGlobalAdmin')->default(false);
        });

        // global admin
        DB::table('userRole')->where('id', 1)->update(
            [
                'customCriteria' => json_encode(['*']),
                'permittedActions' => json_encode(['*']),
                'workflowManagementActions' => json_encode(['*']),
                'fieldPermissions' => json_encode(['*'])
            ]
        );
        // system admin
        DB::table('userRole')->where('id', 2)->update(
            [
                'title' => 'No Action',
                'type' => 'OTHER',
                'isDirectAccess' => false,
                'isInDirectAccess' => false,
                'customCriteria' => json_encode([]),
                'permittedActions' => json_encode(["master-data-write", "master-data-read", "user-read-write", "access-levels-read-write", "workflow-management-read-write", "notice-read-write", "company-info-read-write"]),
                'workflowManagementActions' => json_encode(['*']),
                'fieldPermissions' => json_encode([]),
                'isEditable' => false,
                'isVisibility' => false
            ]
        );
    }
}
