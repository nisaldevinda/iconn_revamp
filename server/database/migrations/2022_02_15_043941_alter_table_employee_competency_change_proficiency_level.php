<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableEmployeeCompetencyChangeProficiencyLevel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employeeCompetency', function (Blueprint $table) {
            $table->string('proficiencyLevel')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employeeCompetency', function (Blueprint $table) {
            $table->integer('proficiencyLevel')->nullable()->default(null)->change();
        });
    }
}
