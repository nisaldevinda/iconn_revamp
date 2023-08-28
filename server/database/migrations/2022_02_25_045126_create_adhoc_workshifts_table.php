<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdhocWorkshiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adhocWorkshifts', function (Blueprint $table) {
            $table->id();
            $table->integer('workShiftId')->nullable()->default(null);
            $table->date('date')->nullable()->default(null);
            $table->integer('employeeId')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('adhocWorkshifts');
    }
}
