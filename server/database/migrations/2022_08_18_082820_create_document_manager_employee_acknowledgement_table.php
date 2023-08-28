<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentManagerEmployeeAcknowledgementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documentManagerEmployeeAcknowledgement', function (Blueprint $table) {
            $table->id();
            $table->integer('documentManagerEmployeeFileId')->nullable()->default(null);
            $table->boolean('isAcknowledged')->default(false);
            $table->boolean('isDocumentUpdated')->default(false);
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
        Schema::dropIfExists('documentManagerEmployeeAcknowledgement');
    }
}
