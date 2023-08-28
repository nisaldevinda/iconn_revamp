<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFilterAndSortColumnsToReportDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reportData', function (Blueprint $table) {
            $table->json('filterValues')->nullable()->default(null);
            $table->json('sortByValues')->nullable()->default(null);
            $table->string('filterCondition')->nullable()->default("AND");



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
            $table->dropColumn('filterValues');
         $table->dropColumn('sortByValues');
            $table->dropColumn('filterCondition');


        });
    }
}
