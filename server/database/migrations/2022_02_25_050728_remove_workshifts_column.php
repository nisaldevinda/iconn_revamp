<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveWorkshiftsColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workShifts', function($table) {
            $table->dropColumn('date');
            $table->decimal('noOfDay', 10, 2)->nullable()->default(null)->change();
            $table->boolean('isWorkPatternShift')->default(false)->after('hasMidnightCrossOver');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workShifts', function($table) {
            $table->date('date')->nullable()->default(null);
            $table->dropColumn('isWorkPatternShift');
            $table->integer('noOfDay')->nullable()->default(null); 
        });
    }
}
