<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewTableCalledPayType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payType', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('code')->nullable()->default(null);
            $table->enum('type', ['GENERAL', 'OVERTIME']);
            $table->decimal('rate', 10, 2)->nullable()->default(0);
            $table->boolean('isDelete')->default(false);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
        });

        
        $configuration = array(
            array(
                "id" => 1,
                "name" => "Regular Time",
                "code" => "RT",
                "type" => "GENERAL",
                "rate" => 0
            )
        );

        if (Schema::hasTable('payType')) {
            foreach ($configuration as $value) {
                DB::table('payType')->insert($value);
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
        Schema::dropIfExists('payType');
    }
}
