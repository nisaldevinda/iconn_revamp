<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeShift extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employeeShift', function (Blueprint $table) {
            $table->id();
            $table->integer('workShiftId')->nullable()->default(null);
            $table->integer('employeeId')->nullable()->default(null);
            $table->date('effectiveDate')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employeeShift');
    }
}
