<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableWorkPatternWeekDayChangeDayType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workPatternWeekDay', function (Blueprint $table) {
            $table->integer('dayTypeId')->nullable()->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workPatternWeekDay', function (Blueprint $table) {
            $table->integer('dayTypeId')->nullable()->default(null)->change();
        });
    }
}
