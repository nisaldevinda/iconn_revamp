<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLeaveManagementRelatedFieldsIntoCompanyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company', function($table) {
            $table->integer('leavePeriodStartingMonth')->default(1)->after('timeZone');
            $table->integer('leavePeriodEndingMonth')->default(12)->after('leavePeriodStartingMonth');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company', function($table) {
            $table->dropColumn('leavePeriodStartingMonth');
            $table->dropColumn('leavePeriodEndingMonth');
        });
    }
}
