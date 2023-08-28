<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddIsGlobalAdminColumnToUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user', function (Blueprint $table) {
            $table->boolean('isGlobalAdmin')->default(false);
        });

        DB::table('user')->where('id', 1)->update(['isGlobalAdmin' => 1]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn('isGlobalAdmin');
        });

        DB::table('user')->where('id', 1)->update(['isGlobalAdmin' => 0]);
    }
}
