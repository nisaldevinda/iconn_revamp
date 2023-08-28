<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeFileUploadInfoTableStructure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('fileUploadInfo');

        Schema::create('documentManagerFile', function (Blueprint $table) {
            $table->id();
            $table->string('documentName');
            $table->string('documentDescription')->nullable()->default(null);
            $table->integer('folderId');
            $table->integer('employeeId')->nullable()->default(null);
            $table->integer('fileId');
            $table->boolean('isDelete')->default(false);
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
        Schema::dropIfExists('documentManagerFile');

        Schema::create('fileUploadInfo', function (Blueprint $table) {
            $table->id();
            $table->string('fileName');
            $table->string('bucket');
            $table->string('objectKey');
            $table->integer('size');
            $table->integer('userId');
            $table->integer('parentId');
            $table->boolean('isDelete')->default(false);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }
}
