<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimeChangeRequestsTable extends Migration
{
    /**
     * Run the migrations.
     * time_change_requests will save attendance records change requests
     * @return void
     */
    public function up()
    {
        Schema::create('time_change_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('shiftId');
            $table->date('shiftDate');
            $table->integer('employeeId');
            $table->integer('approvedId')->nullable()->default(null);
            $table->timestamp('inDateTime');
            $table->timestamp('outDateTime');
            $table->string('reason')->nullable()->default(null);
            $table->integer('type')->nullable()->default(0); // 0=pending, 1=reject, 2=approved // 3=admin
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('time_change_requests');
    }
}
