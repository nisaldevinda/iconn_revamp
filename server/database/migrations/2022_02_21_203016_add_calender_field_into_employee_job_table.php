<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCalenderFieldIntoEmployeeJobTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employeeJob', function (Blueprint $table) {
            $table->integer('calendarId')->default(1)->after('reportsToEmployeeId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employeeJob', function (Blueprint $table) {
            $table->dropColumn('calendarId');
        });
    }
}
