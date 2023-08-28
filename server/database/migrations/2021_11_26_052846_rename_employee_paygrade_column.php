<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameEmployeePaygradeColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee', function(Blueprint $table) {
            $table->renameColumn('payGrade', 'payGradeId');
        });

        Schema::table('employee', function(Blueprint $table) {
            $table->integer('payGradeId')->nullable()->default(null)->change();
        });
    

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee', function(Blueprint $table) {
            $table->renameColumn('payGradeId', 'payGrade');
        });

        Schema::table('employee', function(Blueprint $table) {
            $table->timestamp('payGrade')->nullable()->default(null)->change();
        });
    }
}
