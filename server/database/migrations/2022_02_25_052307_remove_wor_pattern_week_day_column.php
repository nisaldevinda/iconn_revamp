<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveWorPatternWeekDayColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workPatternWeekDay', function($table) {
            $table->dropColumn('noOfDay');
            $table->dropColumn('startTime');
            $table->dropColumn('endTime');
            $table->dropColumn('workHours');
            $table->dropColumn('breakTime');
            $table->dropColumn('hasMidnightCrossOver');
            $table->renameColumn('dayValue', 'dayTypeId');
            $table->integer('workShiftId')->nullable()->default(null)->after('dayValue');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workPatternWeekDay', function($table) {
            $table->integer('noOfDay')->nullable()->default(null);  //[the value can be 0 or 1]
            $table->string('startTime')->nullable();
            $table->string('endTime')->nullable();
            $table->string('workHours')->nullable();
            $table->string('breakTime')->nullable();
            $table->boolean('hasMidnightCrossOver')->default(false);
            $table->renameColumn('dayTypeId', 'dayValue');
            $table->dropColumn('workShiftId');
        });
    }
}
