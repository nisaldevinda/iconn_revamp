<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeOriginalHireDateColumnNameToRecentHireDate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee', function (Blueprint $table) {
            if (Schema::hasColumn('employee', 'originalHireDate')) {
                $table->renameColumn('originalHireDate', 'recentHireDate');
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
        Schema::table('employee', function (Blueprint $table) {
            if (Schema::hasColumn('employee', 'recentHireDate')) {
                $table->renameColumn('recentHireDate', 'originalHireDate');
            }
        });
    }
}
