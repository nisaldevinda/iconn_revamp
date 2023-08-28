<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChartColumnsToReportdataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reportData', function (Blueprint $table) {
            $table->boolean('isChartAvailable')->default(false);     
            $table->string('chartType')->nullable()->default("pieChart");        
            $table->string('aggregateType')->nullable()->default(null);   
            $table->string('aggregateField')->nullable()->default(null);     
            $table->boolean('showSummeryTable')->default(true);  
            $table->boolean('hideDetailedData')->default(false);      
    
 
   


        
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
            $table->dropColumn('isChartAvailable');
            $table->dropColumn('chartType');
            $table->dropColumn('aggregateType');
            $table->dropColumn('aggregateField');
            $table->dropColumn('showSummeryTable');
            $table->dropColumn('hideDetailedData');


        });
    }
}
