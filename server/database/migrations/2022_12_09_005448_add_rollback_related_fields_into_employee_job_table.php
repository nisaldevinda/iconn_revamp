<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRollbackRelatedFieldsIntoEmployeeJobTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employeeJob', function (Blueprint $table) {
            $table->boolean('isRollback')->default(false);
            $table->string('rollbackReason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employeeJob', function (Blueprint $table) {
            $table->dropColumn('isRollback');
            $table->dropColumn('rollbackReason');
        });
    }
}
