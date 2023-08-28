<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkCalendarDateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workCalendarDateNames', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->default(null);
            $table->integer('calendarId')->nullable()->default(null);
            $table->integer('dateTypeId')->nullable()->default(2);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('workCalendarDate');
    }
}

