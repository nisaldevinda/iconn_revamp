<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameAttendanceColumns extends Migration
{
    /**
     * update requiredHours to lateIn.
     * update extraHours to earlyOut.
     * @return void
     */
    public function up()
    {
        Schema::table('attendance', function(Blueprint $table) {
            $table->renameColumn('requiredHours', 'lateIn');
            $table->renameColumn('extraHours', 'earlyOut');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendance', function(Blueprint $table) {
            $table->renameColumn('lateIn', 'requiredHours');
            $table->renameColumn('earlyOut', 'extraHours');
        });
    }
}
