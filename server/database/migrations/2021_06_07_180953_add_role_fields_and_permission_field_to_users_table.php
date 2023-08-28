<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRoleFieldsAndPermissionFieldToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user', function (Blueprint $table) {
            $table->integer('employeeRoleId')->nullable();
            $table->integer('managerRoleId')->nullable();
            $table->json('adminRolesIds')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn('employeeRoleId');
            $table->dropColumn('managerRoleId');
            $table->dropColumn('adminRolesIds');
        });
    }
}
