<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnToWorkShiftPayConfigThresholdTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workShiftPayConfigurationThreshold', function (Blueprint $table) {
            $table->string('validTime')->nullable()->default(null);
            $table->string('hoursPerDay')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workShiftPayConfigurationThreshold', function (Blueprint $table) {
            $table->dropColumn('validTime');
            $table->string('hoursPerDay')->nullable(false)->change();
        });
    }
}
