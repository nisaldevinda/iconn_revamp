<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveEmployeeGroupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leaveEmployeeGroup', function (Blueprint $table) {
            $table->id();
            $table->integer('leaveTypeId');
            $table->json('jobTitles')->nullable()->default(null);
            $table->json('employmentStatuses')->nullable()->default(null);
            $table->json('genders')->nullable()->default(null);
            $table->json('locations')->nullable()->default(null);
            $table->string('minServicePeriod')->nullable();
            $table->string('minPemenancyPeriod')->nullable();
            $table->json('customCriteria')->nullable()->default(null);
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
        Schema::dropIfExists('leaveEmployeeGroup');
    }
}
