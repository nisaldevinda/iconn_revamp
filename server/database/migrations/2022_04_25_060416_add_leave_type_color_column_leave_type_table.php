<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLeaveTypeColorColumnLeaveTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaveType', function (Blueprint $table) {
            $table->string('leaveTypeColor')->default('processing')->after('whoCanAssign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leaveType', function (Blueprint $table) {
            $table->dropColumn('leaveTypeColor');
        });
    }
}
