<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDateTypeIdColumnNameToDayTypeId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workCalendarDateNames', function (Blueprint $table) {
            if (Schema::hasColumn('workCalendarDateNames', 'dateTypeId')) {
                $table->renameColumn('dateTypeId', 'workCalendarDayTypeId');
            }
        });

        Schema::table('workCalendarSpecialDays', function (Blueprint $table) {
            if (Schema::hasColumn('workCalendarSpecialDays', 'dateTypeId')) {
                $table->renameColumn('dateTypeId', 'workCalendarDayTypeId');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workCalendarDateNames', function (Blueprint $table) {
            if (Schema::hasColumn('workCalendarDateNames', 'workCalendarDayTypeId')) {
                $table->renameColumn('workCalendarDayTypeId', 'dateTypeId');
            }
        });

        Schema::table('workCalendarSpecialDays', function (Blueprint $table) {
            if (Schema::hasColumn('workCalendarSpecialDays', 'workCalendarDayTypeId')) {
                $table->renameColumn('workCalendarDayTypeId', 'dateTypeId');
            }
        });
    }
}
