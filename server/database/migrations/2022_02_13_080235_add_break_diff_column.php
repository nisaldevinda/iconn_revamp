<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBreakDiffColumn extends Migration
{
    /**
     * update break total time
     * @return void
     */
    public function up()
    {
        Schema::table('break', function(Blueprint $table) {
            $table->integer('diff')->default(0)->after('out');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('break', function(Blueprint $table) {
            $table->dropColumn('diff');
        });
    }
}
