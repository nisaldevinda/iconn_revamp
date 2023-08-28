<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCompanyInfoTaxIdRegistrationIdDataTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company', function (Blueprint $table) {
            $table->renameColumn('taxId','taxCode')->change();
        });

        Schema::table('company', function (Blueprint $table) {
            $table->string('taxCode')->change();
            $table->string('registrationNo')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company', function (Blueprint $table) {
            $table->renameColumn('taxCode','taxId')->change();
        });

        Schema::table('company', function (Blueprint $table) {
            $table->integer('taxId')->change();
            $table->integer('registrationNo')->change();
        });
    }
}
