<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeEmployeeBankAccountTableFieldsOptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employeeBankAccount', function (Blueprint $table) {
            $table->string('bank')->nullable()->default(null)->change();
            $table->string('branch')->nullable()->default(null)->change();
            $table->integer('accountNumber')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employeeBankAccount', function (Blueprint $table) {
            $table->string('bank')->change();
            $table->string('branch')->change();
            $table->integer('accountNumber')->change();
        });
    }
}
