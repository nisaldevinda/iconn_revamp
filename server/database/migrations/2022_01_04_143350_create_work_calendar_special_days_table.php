<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkCalendarSpecialDaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workCalendarSpecialDays', function (Blueprint $table) {
            $table->id();
            $table->string('calendarId');
            $table->date('date')->nullable()->default(null);
            $table->integer('dateTypeId')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('workCalendarSpecialDays');
    }
}
