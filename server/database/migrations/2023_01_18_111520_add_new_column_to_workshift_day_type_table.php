<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnToWorkshiftDayTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workShiftDayType', function (Blueprint $table) {
            $table->boolean('isBehaveAsNonWorkingDay')->default(false)->after('roundOffToNearest');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workShiftDayType', function (Blueprint $table) {
            $table->dropColumn('isBehaveAsNonWorkingDay');
        });
    }
}
