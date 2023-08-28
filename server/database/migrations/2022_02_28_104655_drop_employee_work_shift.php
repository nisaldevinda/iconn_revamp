<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropEmployeeWorkShift extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // drop the table
        Schema::dropIfExists('employeeWorkShift');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('employeeWorkShift', function (Blueprint $table) {
            $table->id();
            $table->integer('workShiftId')->nullable()->default(null);
            $table->integer('employeeId')->nullable()->default(null);
        });
    }
}
