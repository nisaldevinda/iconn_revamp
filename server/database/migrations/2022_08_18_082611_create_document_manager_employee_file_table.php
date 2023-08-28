<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentManagerEmployeeFileTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documentManagerEmployeeFile', function (Blueprint $table) {
            $table->id();
            $table->integer('documentManagerFileId')->nullable()->default(null);
            $table->integer('employeeId')->nullable()->default(null);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documentManagerEmployeeFile');
    }
}
