<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAzureUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('azureUser', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->integer('userId')->nullable()->default(null);
            $table->integer('employeeId')->nullable()->default(null);
            $table->integer('azureSyncJobId');
            $table->string('azureObjectId');
            $table->json('azureObject');
            $table->enum('status', ['PENDING', 'SUCCESS', 'ERROR']);
            $table->json('responseData')->nullable()->default(null);
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
        Schema::dropIfExists('azureUser');
    }
}
