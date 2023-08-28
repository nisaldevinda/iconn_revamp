<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDataTypeOfBreakHistoryTableTimeStamps extends Migration
{
    /**
     * Run the migrations.
     * change timeStamp fields to dateTime
     * @return void
     */
    public function up()
    {
        Schema::table('break_history', function (Blueprint $table) {
            if (Schema::hasColumn('break_history', 'in')) {
                $table->dateTime('in')->change();
            }

            if (Schema::hasColumn('break_history', 'out')) {
                $table->dateTime('out')->change();
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
        Schema::table('break_history', function (Blueprint $table) {
            if (Schema::hasColumn('break_history', 'in')) {
                $table->timestamp('in')->change();
            }

            if (Schema::hasColumn('break_history', 'out')) {
                $table->timestamp('out')->change();
            }
        });
    }
}
