<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnCalledFirstAccrueAfterNumOfDates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaveAccrual', function (Blueprint $table) {
            $table->integer('firstAccrueAfterNoOfDates')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leaveAccrual', function (Blueprint $table) {
            $table->dropColumn('firstAccrueAfterNoOfDates');
        });
    }
}
