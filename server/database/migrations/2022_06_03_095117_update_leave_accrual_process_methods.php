<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateLeaveAccrualProcessMethods extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE leaveAccrualProcess MODIFY method enum('AUTOMATE', 'MANUAL', 'BACKDATED') NOT NULL DEFAULT 'AUTOMATE';");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE leaveAccrualProcess MODIFY method enum('AUTOMATE', 'MANUAL') NOT NULL DEFAULT 'AUTOMATE';");
    }
}
