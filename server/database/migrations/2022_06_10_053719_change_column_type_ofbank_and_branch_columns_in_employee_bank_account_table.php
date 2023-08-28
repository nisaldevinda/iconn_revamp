<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumnTypeOfbankAndBranchColumnsInEmployeeBankAccountTable extends Migration
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
                $table->integer('bank')->nullable()->change();
            }

            if (Schema::hasColumn('employeeBankAccount', 'branch')) {
                $table->integer('branch')->nullable()->change();
            }

            $table->renameColumn('bank','bankId');
            $table->renameColumn('branch','branchId');
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
                $table->binary('bank')->nullable()->change();
            }

            if (Schema::hasColumn('employeeBankAccount', 'branch')) {
                $table->binary('branch')->nullable()->change();
            }

            $table->renameColumn('bankId','bank');
            $table->renameColumn('branchId','branch');

        });
    }
}
