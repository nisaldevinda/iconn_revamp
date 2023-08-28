<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConfirmationProcessTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('confirmationProcess', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('orgEntityId');
            $table->integer('formTemplateId');
            $table->boolean('isDelete')->default(false);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
        });

        Schema::create('confirmationProcessJobCategories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('confirmationProcessId');
            $table->integer('jobCategoryId');
        });

        Schema::create('confirmationProcessEmploymentTypes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('confirmationProcessId');
            $table->integer('employmentTypeId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('confirmationProcess');
        Schema::dropIfExists('confirmationProcessJobCategories');
        Schema::dropIfExists('confirmationProcessEmploymentTypes');
    }
}
