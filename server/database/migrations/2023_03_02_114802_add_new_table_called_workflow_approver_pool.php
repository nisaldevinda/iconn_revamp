<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewTableCalledWorkflowApproverPool extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workflowApproverPool', function (Blueprint $table) {
            $table->increments('id');
            $table->string('poolName')->nullable()->default(null);
            $table->json('poolPermittedEmployees')->nullable()->default(null);
            $table->boolean('isDelete')->default(false);
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
        Schema::dropIfExists('workflowApproverPool');
    }
}
