<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNameColumnToEmployeeGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaveEmployeeGroup', function (Blueprint $table) {
            $table->string('name')->default(null)->after('id');
            $table->string('comment')->default(null)->after('name');
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
            $table->dropColumn('name');
            $table->dropColumn('comment');
        });
    }
}
