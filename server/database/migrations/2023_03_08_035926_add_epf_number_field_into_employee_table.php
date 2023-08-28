<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEpfNumberFieldIntoEmployeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee', function (Blueprint $table) {
            if (!Schema::hasColumn('employee', 'epfNumber')) {
                $table->string('epfNumber')->nullable()->default(null)->after('attendanceId');
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
        Schema::table('employee', function (Blueprint $table) {
            if (Schema::hasColumn('employee', 'epfNumber')) {
                $table->dropColumn('epfNumber');
            }
        });
    }
}
