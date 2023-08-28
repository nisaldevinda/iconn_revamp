<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBreakHistoryTable extends Migration
{
    /**
     * Run the migrations.
     * to move break records here after time change request accepted which records are out of requested between in and out
     * @return void
     */
    public function up()
    {
        Schema::create('break_history', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('attendanceHistoryId'); // id of attendance history
            $table->timestamp('in'); // need to save in emp time zone
            $table->timestamp('out')->nullable()->default(null); // need to save in emp time zone
            $table->integer('diff')->default(0);
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
        Schema::dropIfExists('break_history');
    }
}
