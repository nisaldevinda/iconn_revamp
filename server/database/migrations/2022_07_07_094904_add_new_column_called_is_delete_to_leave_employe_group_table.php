<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnCalledIsDeleteToLeaveEmployeGroupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaveEmployeeGroup', function (Blueprint $table) {
            $table->boolean('isDelete')->default(false)->after('customCriteria');;
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leaveEmployeeGroup', function (Blueprint $table) {
            $table->dropColumn('isDelete');
        });
    }
}
