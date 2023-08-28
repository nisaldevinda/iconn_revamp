<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertCompanyWiseCommanConfigForMaintainShortLeave extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // CURRENT_RANGE - requested date range , PREVIOUS_RANGE - one month before for requested date range, NEXT_RANGE-one month after for requested date range
        $configuration = array(
            array(
                "id" => 13,
                "key" => "short_leave_maintain_state",
                "description" => "Short Leave Maintain Status Of The Company",
                "type" => "boolean",
                "value" => "false"
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
        $delete = DB::table('configuration')->where('id', 13)->delete();
    }
}
