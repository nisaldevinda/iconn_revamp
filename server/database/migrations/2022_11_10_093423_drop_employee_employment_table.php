<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropEmployeeEmploymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::dropIfExists('employeeEmployment');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employeeEmployment');

        Schema::create('employeeEmployment', function (Blueprint $table) {
            $table->id();
            $table->integer('employeeId');
            $table->date('effectiveDate')->nullable()->default(null);
            $table->integer('employmentStatusId')->nullable()->default(null);
            $table->integer('terminationTypeId')->nullable()->default(null);
            $table->integer('terminationReasonId')->nullable()->default(null);
            $table->string('rehireEligibility')->nullable()->default(null);
            $table->string('comment')->nullable()->default(null);
            $table->date('probationEndDate')->nullable()->default(null);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }
}
