<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeNicNumberFieldTypeInEmployeeDependentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employeeDependent', function (Blueprint $table) {
            $table->string('nicNumber')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employeeDependent', function (Blueprint $table) {
            $table->integer('nicNumber')->change();
        });
    }
}
