<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEtfNumberFieldIntoEmployeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee', function (Blueprint $table) {
            if (!Schema::hasColumn('employee', 'etfNumber')) {
                $table->string('etfNumber')->nullable()->default(null)->after('epfNumber');
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
            if (Schema::hasColumn('employee', 'etfNumber')) {
                $table->dropColumn('etfNumber');
            }
        });
    }
}
