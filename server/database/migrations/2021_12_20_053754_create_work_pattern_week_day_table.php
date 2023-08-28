<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkPatternWeekDayTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workPatternWeekDay', function (Blueprint $table) {
            $table->id();
            $table->integer('workPatternWeekId')->nullable()->default(null);
            $table->integer('dayValue')->nullable()->default(null); // [ The values ranges from 0 => sunday ,1 => Monday .. to 6 => saturday]
            $table->integer('noOfDay')->nullable()->default(null);  //[the value can be 0 or 1]
            $table->string('startTime')->nullable();
            $table->string('endTime')->nullable();
            $table->string('workHours')->nullable();
            $table->string('breakTime')->nullable();
            $table->boolean('hasMidnightCrossOver')->default(false);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
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
        Schema::dropIfExists('workPatternWeekDay');
    }
}
