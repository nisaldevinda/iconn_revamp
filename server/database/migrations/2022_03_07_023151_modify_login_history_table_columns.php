<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyLoginHistoryTableColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('loginHistory', function ($table) {

            $table->dropColumn('status');
            $table->dropColumn('createdBy');
            $table->dropColumn('updatedBy');
            $table->dropColumn('createdAt');
            $table->dropColumn('updatedAt');

            $table->boolean('loginStatus')->after('userId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('loginHistory', function($table) {

            $table->boolean('status');
            $table->integer('createdBy');
            $table->integer('updatedBy');

            $table->dropColumn('loginStatus');
            $table->dropColumn('loginAttempts');
         });
    }
}
