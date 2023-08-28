<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnCalledIsReadOnlyToWorkCalendarDayTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workCalendarDayType', function (Blueprint $table) {
            $table->boolean('isReadOnly')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workCalendarDayType', function (Blueprint $table) {
            $table->dropColumn('isReadOnly');
        });
    }
}
