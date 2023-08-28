<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertCommanConfigForGrabRangeMethodForCalculateDayTypeWiseDateCountForPayRoll extends Migration
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
                "id" => 12,
                "key" => "range_method_for_day_type_wise_count_calculate",
                "description" => "Range method for calculate the day type wise count",
                "type" => "string",
                "value" => json_encode('CURRENT_RANGE') 
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
        $delete = DB::table('configuration')->where('id', 12)->delete();
    }
}
