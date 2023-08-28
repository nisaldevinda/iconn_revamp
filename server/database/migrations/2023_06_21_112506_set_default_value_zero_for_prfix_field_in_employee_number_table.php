<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SetDefaultValueZeroForPrfixFieldInEmployeeNumberTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employeeNumberConfiguration', function (Blueprint $table) {
            $table->string('prefix')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employeeNumberConfiguration', function (Blueprint $table) {
            $table->string('prefix')->change();
        });
    }
}
