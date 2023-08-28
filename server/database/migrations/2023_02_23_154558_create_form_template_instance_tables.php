<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateFormTemplateInstanceTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('formTemplateInstance', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('employeeId');
            $table->integer('formtemplateId');
            $table->uuid('hash');
            $table->enum('status', ['PENDING', 'DRAFT', 'COMPLETED', 'CANCELED'])->default('PENDING');
            $table->integer('authorizedEmployeeId');
            $table->json('blueprint');
            $table->json('response')->nullable()->default(null);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
        });

        Schema::create('employeeJobformTemplateInstance', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('employeeJobId');
            $table->integer('formTemplateInstanceId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('formTemplateInstance');
        Schema::dropIfExists('employeeJobformTemplateInstance');
    }
}
