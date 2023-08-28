<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewConfigurationsForMaintainOt extends Migration
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
                "id" => 2,
                "key" => "over_time_maintain_state",
                "description" => "over time maintaining state of company",
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
        $delete = DB::table('configuration')->where('id', 2)->delete();
    }
}
