<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClaimTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('claimType', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('orgEntityId');
            $table->string('typeName')->nullable()->default(null);
            $table->integer('claimCategoryId');
            $table->enum('amountType', ['UNLIMITED', 'MAX_AMOUNT'])->nullable()->default(null);
            $table->string('maxAmount')->nullable()->default(null);
            $table->enum('orderType', ['MONTHLY', 'ANNUALY'])->nullable()->default(null);
            $table->boolean('isAllowAttachment')->default(false);
            $table->boolean('isAttachmentMandatory')->default(false);
            $table->boolean('isAllocationEnable')->default(false);
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
        Schema::dropIfExists('claimType');
    }
}
