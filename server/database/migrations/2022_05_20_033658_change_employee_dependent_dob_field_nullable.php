<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeEmployeeDependentDobFieldNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employeeDependent', function (Blueprint $table) {
            if (Schema::hasColumn('employeeDependent', 'dateOfBirth')) {
                $table->date('dateOfBirth')->nullable()->default(null)->change();
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
        Schema::table('employeeDependent', function (Blueprint $table) {
            if (Schema::hasColumn('employeeDependent', 'dateOfBirth')) {
                $table->date('dateOfBirth')->change();
            }
        });
    }
}
