<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldPermissionsColumnToUserRoleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('userRole', function (Blueprint $table) {
            $table->dropColumn('readableFields');
            $table->dropColumn('editableFields');
            $table->json('fieldPermissions')->default("{}")->after('permittedActions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('userRole', function (Blueprint $table) {
            $table->json('readableFields')->after('permittedActions');
            $table->json('editableFields')->after('permittedActions');
            $table->dropColumn('fieldPermissions');
        });
    }
}
