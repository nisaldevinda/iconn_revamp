<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnToEmployeeWorkPatternTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employeeWorkPattern', function (Blueprint $table) {
            $table->boolean('isActivePattern')->default(true)->after('effectiveDate');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employeeWorkPattern', function (Blueprint $table) {
            $table->dropColumn('isActivePattern');
        });
    }
}
