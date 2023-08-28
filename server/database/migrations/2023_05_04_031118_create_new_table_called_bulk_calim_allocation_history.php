<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewTableCalledBulkCalimAllocationHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bulkClaimAllocationHistory', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('financialYearId')->nullable()->default(null);
            $table->integer('claimTypeId')->nullable()->default(null);
            $table->enum('allocationType', ['designation', 'employmentStatus', 'jobCategory']);
            $table->float('allocatedAmount', 10, 2)->default(0);
            $table->json('newlyAffectedEmployees')->default("[]");
            $table->json('updatedEmployees')->default("[]");
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
        Schema::dropIfExists('bulkClaimAllocationHistory');
    }
}
