<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumnTypesOfEmployeeGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaveEmployeeGroup', function (Blueprint $table) {
            $table->integer('minServicePeriod')->change();
            $table->integer('minPemenancyPeriod')->change();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leaveEmployeeGroup', function (Blueprint $table) {
            $table->string('minServicePeriod')->change();
            $table->string('minPemenancyPeriod')->change();


        });
    }
}
