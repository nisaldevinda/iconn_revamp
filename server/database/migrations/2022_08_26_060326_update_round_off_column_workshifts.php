<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRoundOffColumnWorkshifts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workShifts', function (Blueprint $table) {
            DB::statement("ALTER TABLE workShifts MODIFY roundOffMethod ENUM('NO_ROUNDING', 'ROUND_UP','ROUND_DOWN') ");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workShifts', function (Blueprint $table) {
            DB::statement("ALTER TABLE workShifts MODIFY roundOffMethod ENUM('NO_ROUNDING', 'ROUND_UP') ");
        });
    }
}
