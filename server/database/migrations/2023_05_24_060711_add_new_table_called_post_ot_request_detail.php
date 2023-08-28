<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewTableCalledPostOtRequestDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('postOtRequestDetail', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('postOtRequestId')->nullable()->default(null);
            $table->integer('summaryId')->nullable()->default(null);
            $table->integer('shiftId')->nullable()->default(null);
            $table->dateTime('actualIn')->nullable()->default(null);
            $table->dateTime('actualOut')->nullable()->default(null);
            $table->integer('workTime')->default(0);
            $table->integer('totalActualOt')->default(0);
            $table->integer('totalRequestedOt')->default(0);
            $table->integer('totalApprovedOt')->default(0);
            $table->json('otDetails')->default('{}');
            $table->enum('status', ['PENDING', 'CANCELED', 'APPROVED', 'REJECTED']);
            $table->json('approveUserComment')->nullable()->default(null);
            $table->string('requestedEmployeeComment')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('postOtRequestDetail');
    }
}
