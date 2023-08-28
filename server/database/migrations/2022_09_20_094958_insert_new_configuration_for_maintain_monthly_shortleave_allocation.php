<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class InsertNewConfigurationForMaintainMonthlyShortleaveAllocation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $configuration = array(
            array(
                "id" => 3,
                "key" => "monthly_short_leave_allocation",
                "description" => "Monthly Short Leave Allocation Of Company",
                "type" => "numeric",
                "value" => "2"
            )
        );

        if (Schema::hasTable('configuration')) {
            foreach ($configuration as $value) {
                DB::table('configuration')->insert($value);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $record = DB::table('configuration')->where('id', 3)->get();
        if (!is_null($record)) {
            $delete = DB::table('configuration')->where('id', 3)->delete();
        }

    }
}
