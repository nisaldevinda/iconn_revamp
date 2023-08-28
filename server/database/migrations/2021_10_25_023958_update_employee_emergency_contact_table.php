<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEmployeeEmergencyContactTable extends Migration
{
    /**
     * Run the migrations.
     * Change column type to string
     * @return void
     */
    public function up()
    {
        Schema::table('employeeEmergencyContact', function (Blueprint $table) {
            $table->string('mobilePhone')->change();
            $table->string('homePhone')->nullable()->default(null)->change();
            $table->string('workPhone')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     * Reverse column type to integer
     * @return void
     */
    public function down()
    {
        Schema::table('employeeEmergencyContact', function (Blueprint $table) {
            $table->integer('mobilePhone')->change();
            $table->integer('homePhone')->nullable()->default(null)->change();
            $table->integer('workPhone')->nullable()->default(null)->change();
        });
    }
}
