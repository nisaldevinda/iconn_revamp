<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumnTypeOfProRateRelateColumnInLeaveAccrual extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaveAccrual', function (Blueprint $table) {
            if (Schema::hasColumn('leaveAccrual', 'proRateMethodFirstAccrualForAnnualFrequency')) {
                $table->integer('proRateMethodFirstAccrualForAnnualFrequency')->nullable()->change();
            }
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
            if (Schema::hasColumn('leaveAccrual', 'proRateMethodFirstAccrualForAnnualFrequency')) {
                $table->json('proRateMethodFirstAccrualForAnnualFrequency')->nullable()->change();
            }
        });
    }
}
