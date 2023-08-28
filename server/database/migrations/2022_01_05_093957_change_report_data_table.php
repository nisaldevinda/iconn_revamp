<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeReportDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reportData', function (Blueprint $table) {
            $table->json('targetKeys')->default('{}');
            $table->boolean('isDelete')->default(false); 
               
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
            $table->dropColumn('targetKeys');
            $table->dropColumn('isDelete');

        });
    }
}
