<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeEmergencyContactTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employeeEmergencyContact', function (Blueprint $table) {
            $table->id();
            $table->integer('employeeId');
            $table->string('name');
            $table->integer('relationshipId');
            $table->integer('mobilePhone');
            $table->integer('homePhone')->nullable()->default(null);
            $table->integer('workPhone')->nullable()->default(null);
            $table->string('emailAddress')->nullable()->default(null);
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
        Schema::dropIfExists('employeeEmergencyContact');
    }
}
