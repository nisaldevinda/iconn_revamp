<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewConfigurationForMaintainTimeBasePayCofigurations extends Migration
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
                "id" => 10,
                "key" => "maintain_time_base_pay_configuration",
                "description" => "Maintain Time Base Pay Configuration",
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
        $delete = DB::table('configuration')->where('id', 10)->delete();
    }
}
