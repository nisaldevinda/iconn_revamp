<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDelayedQueueJobTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delayed_queue_job', function (Blueprint $table) {
            $table->id();
            $table->string('queueJobId');
            $table->string('parentModel')->nullable()->default(null);
            $table->integer('parentRecordId')->nullable()->default(null);
            $table->string('childModel')->nullable()->default(null);
            $table->integer('childRecordId')->nullable()->default(null);
            $table->boolean('isCancelled')->default(false);
            $table->integer('executeAt');
            // $table->integer('executeBy')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('delayed_queue_job');
    }
}
