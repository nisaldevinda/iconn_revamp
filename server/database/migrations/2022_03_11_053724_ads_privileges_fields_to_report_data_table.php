<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AdsPrivilegesFieldsToReportDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reportData', function (Blueprint $table) {
            $table->boolean('isAdminReport')->default(true);
            $table->boolean('isManagerReport')->default(false);
            $table->boolean('isEmployeeReport')->default(false);


            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reportData', function (Blueprint $table) {
            $table->dropColumn('isAdminReport');
            $table->dropColumn('isManagerReport');            
            $table->dropColumn('isEmployeeReport');

        });
    }
}
