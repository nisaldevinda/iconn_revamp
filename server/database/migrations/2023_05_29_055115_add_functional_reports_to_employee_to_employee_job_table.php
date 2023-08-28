<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFunctionalReportsToEmployeeToEmployeeJobTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employeeJob', function (Blueprint $table) {
            if (!Schema::hasColumn('employeeJob', 'functionalReportsToEmployeeId')) {
                $table->integer('functionalReportsToEmployeeId')->nullable()->default(null)->after('reportsToEmployeeId');
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
        Schema::table('employeeJob', function (Blueprint $table) {
            if (Schema::hasColumn('employeeJob', 'functionalReportsToEmployeeId')) {
                $table->dropColumn('functionalReportsToEmployeeId');
            }
        });
    }
}
