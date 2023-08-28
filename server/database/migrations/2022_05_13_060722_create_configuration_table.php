<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConfigurationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('configuration', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key');
            $table->string('description')->nullable()->default(null);
            $table->enum('type', ['numeric', 'string', 'json', 'boolean']);
            $table->json('value')->nullable()->default(null);
        });

        
        $configuration = array(
            array(
                "id" => 1,
                "key" => "short_leave_duration",
                "description" => "short leave duration in minutes",
                "type" => "numeric",
                "value" => "90"
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
        Schema::dropIfExists('configuration');
    }
}
