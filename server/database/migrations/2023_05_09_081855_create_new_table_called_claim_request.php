<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewTableCalledClaimRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('claimRequest', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('employeeId')->nullable()->default(null);
            $table->integer('financialYearId')->nullable()->default(null);
            $table->integer('claimTypeId')->nullable()->default(null);
            $table->string('claimMonth')->nullable()->default(null);
            $table->float('totalReceiptAmount', 10, 2)->default(0);
            $table->integer('currentState')->nullable()->default(null);
            $table->integer('workflowInstanceId')->nullable()->default(null);
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
        Schema::dropIfExists('claimRequest');
    }
}
