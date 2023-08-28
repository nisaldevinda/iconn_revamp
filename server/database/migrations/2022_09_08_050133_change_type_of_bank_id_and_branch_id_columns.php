<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangeTypeOfBankIdAndBranchIdColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            DB::statement('ALTER TABLE employeeBankAccount MODIFY bankId INT NULL DEFAULT NULL ');
            DB::statement('ALTER TABLE employeeBankAccount MODIFY branchId INT NULL DEFAULT NULL ');
        } catch (\Exception $ex) {
            error_log($ex->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        try {
            Schema::table('employeeBankAccount', function (Blueprint $table) {
                if (Schema::hasColumn('employeeBankAccount', 'bankId')) {
                    $table->binary('bankId')->nullable()->change();
                }
    
                if (Schema::hasColumn('employeeBankAccount', 'branchId')) {
                    $table->binary('branchId')->nullable()->change();
                }
            });
        } catch (\Exception $ex) {
            error_log($ex->getMessage());
        }
    }
}
