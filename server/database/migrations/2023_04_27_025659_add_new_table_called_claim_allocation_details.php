<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewTableCalledClaimAllocationDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('claimAllocationDetail', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('employeeId')->nullable()->default(null);
            $table->integer('financialYearId')->nullable()->default(null);
            $table->integer('claimTypeId')->nullable()->default(null);
            $table->float('allocatedAmount', 10, 2)->default(0);
            $table->float('usedAmount', 10, 2)->default(0);
            $table->float('pendingAmount', 10, 2)->default(0);
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
        Schema::dropIfExists('claimAllocationDetail');
    }
}
