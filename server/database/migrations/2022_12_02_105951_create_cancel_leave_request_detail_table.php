<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCancelLeaveRequestDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cancelLeaveRequestDetail', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('cancelLeaveRequestId')->nullable()->default(null);
            $table->date('cancelLeaveDate')->nullable()->default(null);
            $table->enum('status', ['PENDING', 'CANCELED', 'APPROVED', 'REJECTED']);
            $table->enum('cancelLeavePeriodType', ['FULL_DAY', 'FIRST_HALF_DAY', 'SECOND_HALF_DAY']);
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cancelLeaveRequestDetail');
    }
}
