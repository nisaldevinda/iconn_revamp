<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBreakTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('break', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('attendanceId'); 
            $table->timestamp('in'); // need to save in emp time zone
            $table->timestamp('out')->nullable()->default(null); // need to save in emp time zone
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
        Schema::dropIfExists('break');
    }
}
