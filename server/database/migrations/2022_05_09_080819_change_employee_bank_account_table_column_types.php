<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeEmployeeBankAccountTableColumnTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employeeBankAccount', function (Blueprint $table) {
            if (Schema::hasColumn('employeeBankAccount', 'bank')) {
                $table->binary('bank')->nullable()->change();
            }

            if (Schema::hasColumn('employeeBankAccount', 'branch')) {
                $table->binary('branch')->nullable()->change();
            }

            if (Schema::hasColumn('employeeBankAccount', 'accountNumber')) {
                $table->binary('accountNumber')->nullable()->change();
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
        Schema::table('employeeBankAccount', function (Blueprint $table) {
            if (Schema::hasColumn('employeeBankAccount', 'bank')) {
                $table->string('bank', 10, 2)->change();
            }

            if (Schema::hasColumn('employeeBankAccount', 'branch')) {
                $table->string('branch')->change();
            }

            if (Schema::hasColumn('employeeBankAccount', 'accountNumber')) {
                $table->integer('accountNumber')->change();
            }

        });
    }
}
