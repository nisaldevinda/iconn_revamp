<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDataTypeOfSalaryMinimunMaximumFieldsInPayGradesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payGrades', function (Blueprint $table) {
            if (Schema::hasColumn('payGrades', 'minimumSalary')) {
                $table->integer('minimumSalary')->change();
            }

            if (Schema::hasColumn('payGrades', 'maximumSalary')) {
                $table->integer('maximumSalary')->change();
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
        Schema::table('payGrades', function (Blueprint $table) {
            if (Schema::hasColumn('payGrades', 'minimumSalary')) {
                $table->string('minimumSalary')->change();
            }

            if (Schema::hasColumn('payGrades', 'maximumSalary')) {
                $table->string('maximumSalary')->change();
            }
        });
    }
}
