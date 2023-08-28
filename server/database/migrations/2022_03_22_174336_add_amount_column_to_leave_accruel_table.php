<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAmountColumnToLeaveAccruelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaveAccrual', function (Blueprint $table) {
            $table->float('amount', 5, 2)->default(0)->after('proRateMethodFirstAccrualForAnnualFrequency');
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
            $table->dropColumn('amount');
        });
    }
}
