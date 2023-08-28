<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewTableCalledClaimPackages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('claimPackages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('allowOrgEntityId')->nullable()->default(null);
            $table->json('allowJobCategories')->default("[]");
            $table->json('allowEmploymentStatuses')->default("[]");
            $table->json('allocatedClaimTypes')->default("[]");
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
        Schema::dropIfExists('claimPackages');
    }
}
