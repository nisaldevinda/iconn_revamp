<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDataTypeOfEmploymentStatusIdFieldInEmployeeEmploymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employeeEmployment', function (Blueprint $table) {
            $table->integer('employmentStatusId')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employeeEmployment', function (Blueprint $table) {
            $table->string('employmentStatusId')->change();
        });
    }
}
