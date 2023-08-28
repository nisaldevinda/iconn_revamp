<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBulkLetterLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bulkLetterLog', function (Blueprint $table) {
            $table->id();
            $table->integer('templateId')->nullable()->default(null);
            $table->enum('status', ['PENDING', 'COMPLETED', 'ERROR'])->default('PENDING');
            $table->string('note')->nullable();
            $table->integer('createdBy')->nullable()->default(null);
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
        Schema::dropIfExists('bulkLetterLog');
    }
}
