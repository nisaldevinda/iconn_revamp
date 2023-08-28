<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveTotalPayableAndCostToCompanyFieldsFromSalaryComponentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('salaryComponents', function (Blueprint $table) {
            $table->dropColumn('costToCompany');
            $table->dropColumn('totalPayable');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('salaryComponents', function (Blueprint $table) {
            $table->string('costToCompany')->nullable()->default(false);
            $table->string('totalPayable')->nullable()->default(false);
        });
    }
}
