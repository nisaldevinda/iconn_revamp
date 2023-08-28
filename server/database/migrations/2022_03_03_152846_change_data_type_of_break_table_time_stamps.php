<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDataTypeOfBreakTableTimeStamps extends Migration
{
    /**
     * Run the migrations.
     * change timeStamp fields to dateTime
     * @return void
     */
    public function up()
    {
        Schema::table('break', function (Blueprint $table) {
            if (Schema::hasColumn('break', 'in')) {
                $table->dateTime('in')->change();
            }

            if (Schema::hasColumn('break', 'out')) {
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
        Schema::table('break', function (Blueprint $table) {
            if (Schema::hasColumn('break', 'in')) {
                $table->timestamp('in')->change();
            }

            if (Schema::hasColumn('break', 'out')) {
                $table->timestamp('out')->change();
            }
        });
    }
}
