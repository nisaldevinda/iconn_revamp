<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateManualProcessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manualProcess', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('userId');
            $table->enum('type', ['LEAVE_ACCRUAL', 'ATTENDANCE_PROCESS']);
            $table->enum('status', ['PENDING', 'COMPLETED', 'ERROR'])->default('PENDING');
            $table->string('note')->nullable();
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
        Schema::dropIfExists('manualProcess');
    }
}
