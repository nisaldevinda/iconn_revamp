<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeJourneyBulkUploadHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employeeJourneyUploadHistory', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('employeeJourneyType', ['PROMOTIONS', 'CONFIRMATION_CONTRACTS', 'TRANSFERS', 'RESIGNATIONS']);
            $table->json('affectedIds')->nullable()->default("[]");
            $table->boolean('isRollback')->default(false);
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
        Schema::dropIfExists('employeeJourneyUploadHistory');
    }
}
