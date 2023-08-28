<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeCompetencyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employeeCompetency', function (Blueprint $table) {
            $table->id();
            $table->integer('employeeId');
            $table->integer('competencyTypeId')->nullable()->default(null);
            $table->integer('competencyId')->nullable()->default(null);
            $table->integer('proficiencyLevelId')->nullable()->default(null);
            $table->date('lastEvaluationDate')->nullable()->default(null);
            $table->string('comment')->nullable()->default(null);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employeeCompetency');
    }
}
