<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurrentFieldsInEmployeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee', function (Blueprint $table) {
            $table->integer('currentEmploymentsId')->nullable();
            $table->integer('currentJobsId')->nullable();
            $table->integer('currentSalariesId')->nullable();
            $table->integer('currentBankAccountsId')->nullable();
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
            $table->dropColumn('currentEmploymentsId');
            $table->dropColumn('currentJobsId');
            $table->dropColumn('currentSalariesId');
            $table->dropColumn('currentBankAccountsId');
        });
    }
}
