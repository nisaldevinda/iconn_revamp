<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAzureSyncDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('azureSyncJob', function (Blueprint $table) {
            $table->increments('id');
            $table->json('fieldMap');
            $table->integer('totalCount')->default(0);
            $table->integer('successCount')->default(0);
            $table->integer('errorCount')->default(0);
            $table->enum('status', ['PENDING', 'PROCESSING', 'SUCCESS', 'ERROR']);
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
        Schema::dropIfExists('azureSyncJob');
    }
}
