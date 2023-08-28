<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Type\Integer;

class UpdateCompanyInfoLeaveConfigFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('company')->where('id', 1)
            ->update(['leavePeriodStartingMonth' => 1, 'leavePeriodEndingMonth' => 12]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('company')->where('id', 1)
            ->update(['leavePeriodStartingMonth' => 1970, 'leavePeriodEndingMonth' => 1970]);
    }
}
