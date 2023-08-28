<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnToWorkShiftPayConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workShiftPayConfiguration', function (Blueprint $table) {
            $table->enum('payConfigType', ['HOUR_BASE', 'TIME_BASE']);
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workShiftPayConfiguration', function (Blueprint $table) {
            $table->dropColumn('payConfigType');
        });
    }
}
