<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameDayTypeTableAndworkCalendarDateTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('dayType', 'dayOfWeek');
        Schema::rename('workCalendarDateType', 'workCalendarDayType');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('dayOfWeek', 'dayType');
        Schema::rename('workCalendarDayType', 'workCalendarDateType');
    }
}
