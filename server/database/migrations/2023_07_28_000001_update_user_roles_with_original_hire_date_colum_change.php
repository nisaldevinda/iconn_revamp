<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateUserRolesWithOriginalHireDateColumChange extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('userRole')
            ->where('fieldPermissions', 'LIKE', '%"originalHireDate"%')
            ->update([
                'fieldPermissions' => DB::raw("REPLACE(fieldPermissions, 'originalHireDate', 'recentHireDate')")
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('userRole')
            ->where('fieldPermissions', 'LIKE', '%"recentHireDate"%')
            ->update([
                'fieldPermissions' => DB::raw("REPLACE(fieldPermissions, 'recentHireDate', 'originalHireDate')")
            ]);
    }
}
