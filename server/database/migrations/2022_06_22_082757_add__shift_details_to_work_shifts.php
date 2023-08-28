<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShiftDetailsToWorkShifts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workShifts', function (Blueprint $table) {
            $table->dropColumn('isWorkPatternShift');
            $table->string('code')->nullable()->after('name');
            $table->string('color')->nullable()->after('code');
            $table->string('halfDayLength')->nullable()->after('hasMidnightCrossOver');
            $table->string('gracePeriod')->nullable()->after('halfDayLength');
            $table->boolean('isOTEnabled')->default(false)->after('gracePeriod');
            $table->boolean('inOvertime')->default(false)->after('isOTEnabled');
            $table->boolean('outOvertime')->default(false)->after('inOvertime');
            $table->boolean('deductLateFromOvertime')->default(false)->after('outOvertime');
            $table->string('minimumOT')->nullable()->after('deductLateFromOvertime');
            $table->enum('roundOffMethod', ['NO_ROUNDING', 'ROUND_UP'])->nullable()->after('minimumOT');
            $table->enum('roundOffToNearest', ['5_MINUTES','15_MINUTES', '30_MINUTES','1_HOUR'])->nullable()->after('roundOffMethod');
            $table->enum('shiftType', ['GENERAL', 'FLEXI'])->nullable()->after('roundOffToNearest');
            $table->boolean('isActive')->default(false)->after('shiftType');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workShifts', function (Blueprint $table) {
            $table->boolean('isWorkPatternShift')->default(false);
            $table->dropColumn('code');
            $table->dropColumn('color');
            $table->dropColumn('halfDayLength');
            $table->dropColumn('gracePeriod');
            $table->dropColumn('isOTEnabled');
            $table->dropColumn('inOvertime');
            $table->dropColumn('outOvertime');
            $table->dropColumn('deductLateFromOvertime');
            $table->dropColumn('minimumOT');
            $table->dropColumn('roundOffMethod');
            $table->dropColumn('roundOffToNearest');
            $table->dropColumn('shiftType');
            $table->dropColumn('isActive');
        });
    }
}
