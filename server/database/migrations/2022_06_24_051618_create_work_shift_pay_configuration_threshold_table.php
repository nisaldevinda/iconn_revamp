<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkShiftPayConfigurationThresholdTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workShiftPayConfigurationThreshold', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('workShiftPayConfigurationId');
            $table->string('payTypeId');
            $table->string('hoursPerDay');
            $table->integer('thresholdSequence');
            $table->enum('thresholdType', ['Upto', 'After']);
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('workShiftPayConfigurationThreshold');
    }
}
