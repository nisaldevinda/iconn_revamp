<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkflowEmployeeGroupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workflowEmployeeGroup', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->default(null);
            $table->string('comment')->default(null);
            $table->integer('contextId');
            $table->json('jobTitles')->nullable()->default(null);
            $table->json('employmentStatuses')->nullable()->default(null);
            $table->json('departments')->nullable()->default(null);
            $table->json('locations')->nullable()->default(null);
            $table->json('divisions')->nullable()->default(null);
            $table->json('reportingPersons')->nullable()->default(null);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->boolean('isDelete')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('workflowEmployeeGroup');
    }
}
