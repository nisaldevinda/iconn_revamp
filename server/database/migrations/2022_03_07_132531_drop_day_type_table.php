<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropDayTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('dayType');

        if (!Schema::hasTable('dayType')) {
            Schema::create('dayType', function (Blueprint $table) {
               $table->integer('id');
               $table->string('dayName');
               $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
               $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
           });

           $dayTypeData = array(
            [
                'id' => 0,
                'dayName' => 'sunday',
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id' => 1,
                'dayName' => 'monday',
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id' => 2,
                'dayName' => 'tuesday',
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id' => 3,
                'dayName' => 'wednesday',
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id' => 4,
                'dayName' => 'thursday',
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id' => 5,
                'dayName' => 'friday',
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id' => 6,
                'dayName' => 'saturday',
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ]
            
           );
           DB::table('dayType')->insert($dayTypeData);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dayType');
    }
}
