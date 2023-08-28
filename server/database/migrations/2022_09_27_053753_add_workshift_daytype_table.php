<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWorkshiftDaytypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workShifts', function (Blueprint $table) {
            $table->dropColumn('noOfDay');  
            $table->dropColumn('startTime');
            $table->dropColumn('endTime');
            $table->dropColumn('workHours');
            $table->dropColumn('breakTime');
            $table->dropColumn('hasMidnightCrossOver');
            $table->dropColumn('halfDayLength');
            $table->dropColumn('gracePeriod');
            $table->dropColumn('isOTEnabled');
            $table->dropColumn('inOvertime');
            $table->dropColumn('outOvertime');
            $table->dropColumn('deductLateFromOvertime');
            $table->dropColumn('minimumOT');
            $table->dropColumn('roundOffMethod');
            $table->dropColumn('roundOffToNearest');
           
        });

        Schema::create('workShiftDayType', function (Blueprint $table) {
            $table->id();
            $table->integer('dayTypeId')->nullable();
            $table->integer('workShiftId')->nullable();
            $table->decimal('noOfDay', 10, 2)->nullable()->default(null);  
            $table->string('startTime')->nullable();
            $table->string('endTime')->nullable();
            $table->string('workHours')->nullable();
            $table->string('breakTime')->nullable();
            $table->boolean('hasMidnightCrossOver')->default(false);
            $table->string('halfDayLength')->nullable();
            $table->string('gracePeriod')->nullable();
            $table->boolean('isOTEnabled')->default(false);
            $table->boolean('inOvertime')->default(false);
            $table->boolean('outOvertime')->default(false);
            $table->boolean('deductLateFromOvertime')->default(false);
            $table->string('minimumOT')->nullable();
            $table->enum('roundOffMethod', ['NO_ROUNDING', 'ROUND_UP','ROUND_DOWN'])->nullable();
            $table->enum('roundOffToNearest', ['5_MINUTES','15_MINUTES', '30_MINUTES','1_HOUR'])->nullable();
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
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
           $table->integer('noOfDay')->nullable()->default(null);  
           $table->string('startTime')->nullable();
           $table->string('endTime')->nullable();
           $table->string('workHours')->nullable();
           $table->string('breakTime')->nullable();
           $table->boolean('hasMidnightCrossOver')->default(false);
           $table->string('halfDayLength')->nullable()->after('hasMidnightCrossOver');
           $table->string('gracePeriod')->nullable()->after('halfDayLength');
           $table->boolean('isOTEnabled')->default(false)->after('gracePeriod');
           $table->boolean('inOvertime')->default(false)->after('isOTEnabled');
           $table->boolean('outOvertime')->default(false)->after('inOvertime');
           $table->boolean('deductLateFromOvertime')->default(false)->after('outOvertime');
           $table->string('minimumOT')->nullable()->after('deductLateFromOvertime');
           $table->enum('roundOffMethod', ['NO_ROUNDING', 'ROUND_UP' ,'ROUND_DOWN'])->nullable()->after('minimumOT');
           $table->enum('roundOffToNearest', ['5_MINUTES','15_MINUTES', '30_MINUTES','1_HOUR'])->nullable()->after('roundOffMethod');
        });

        Schema::dropIfExists('workShiftDayType');
    }
}
