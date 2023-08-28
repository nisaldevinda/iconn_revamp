<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameSalarycomponentColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payGrades', function(Blueprint $table) {
            $table->renameColumn('salaryComponent', 'salaryComponentId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payGrades', function(Blueprint $table) {
            $table->renameColumn('salaryComponentId', 'salaryComponent');
        });
    }
}
