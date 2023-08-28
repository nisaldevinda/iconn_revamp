<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeSalaryComponentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('salaryComponents', function (Blueprint $table) {
            // $table->enum('salaryType', ['BASE_PAY', 'FIXED_ALLOWANCE'])->nullable()->default('BASE_PAY')->change();
            $table->integer('countryId')->nullable()->default(null);
            // $table->enum('valueType', ['MONTHLY_AMOUNT', 'ANNUAL_AMOUNT'])->nullable()->default(null)->change();
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
            // $table->string('salaryType');
            $table->dropColumn('countryId');
            // $table->string('valueType');
        });
    }
}
