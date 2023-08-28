<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailTemplateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emailTemplate', function (Blueprint $table) {
            $table->increments('id');
            $table->string('formName')->nullable()->default(null);
            $table->string('alertName')->nullable()->default(null);
            $table->string('description')->nullable()->default(null);
            $table->boolean('status')->nullable()->default(false);
            $table->string('from')->nullable()->default(null);
            $table->string('to')->nullable()->default(null);
            $table->string('cc')->nullable()->default(null);
            $table->string('bcc')->nullable()->default(null);
            $table->string('subject')->nullable()->default(null);
            $table->string('type')->nullable()->default(null);
            $table->string('actionId')->nullable()->default(null);
            $table->string('date')->nullable()->default(null);
            $table->string('frequency')->nullable()->default(null);
            $table->string('reminderType')->nullable()->default(null);
            $table->string('reminderValue')->nullable()->default(null);
            $table->string('contentId')->nullable()->default(null);
            $table->boolean('isDelete')->default(false);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('emailTemplate');
    }
}
