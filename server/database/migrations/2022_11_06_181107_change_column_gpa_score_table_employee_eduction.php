<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumnGpaScoreTableEmployeeEduction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employeeEducation', function (Blueprint $table) {

            if (Schema::hasColumn('employeeEducation', 'gpaScore')) {
                $table->decimal('gpaScore', 10, 2)->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employeeEducation', function (Blueprint $table) {

            if (Schema::hasColumn('employeeEducation', 'gpaScore')) {
                $table->integer('gpaScore')->change();
            }
        });
    }
}
