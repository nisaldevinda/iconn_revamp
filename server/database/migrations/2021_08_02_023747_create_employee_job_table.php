<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeJobTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employeeJob', function (Blueprint $table) {
            $table->id();
            $table->integer('employeeId');
            $table->date('effectiveDate')->nullable()->default(null);
            $table->integer('locationId');
            $table->integer('departmentId')->nullable()->default(null);
            $table->integer('divisionId')->nullable()->default(null);
            $table->integer('jobTitleId')->nullable()->default(null);
            $table->integer('reportsToEmployeeId')->nullable()->default(null);
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
        Schema::dropIfExists('employeeJob');
    }
}
