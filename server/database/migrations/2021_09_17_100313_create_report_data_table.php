<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reportData', function (Blueprint $table) {
            $table->increments('id');
            $table->string('reportName')->unique();
            $table->string('displayName')->unique();
            $table->json('selectedTables')->default('{}');
            $table->json('joinCriterias')->default('{}');
            $table->json('filterCriterias')->default('{}');
            $table->json('derivedFields')->default('{}');
            $table->json('orderBy')->default('{}');
            $table->json('groupBy')->default('{}');
            $table->integer('pageSize')->default(20);
            $table->string('outputMethod')->default('csv');
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
        Schema::dropIfExists('reportData');
    }
}