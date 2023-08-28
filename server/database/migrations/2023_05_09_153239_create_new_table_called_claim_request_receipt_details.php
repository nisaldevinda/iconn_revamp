<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewTableCalledClaimRequestReceiptDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('claimRequestReceiptDetails', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('claimRequestId')->nullable()->default(null);
            $table->string('receiptNumber')->nullable()->default(null);
            $table->date('receiptDate')->nullable()->default(null);
            $table->float('receiptAmount', 10, 2)->default(0);
            $table->integer('fileAttachementId')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('claimRequestReceiptDetails');
    }
}
