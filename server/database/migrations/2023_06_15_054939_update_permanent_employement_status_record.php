<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdatePermanentEmployementStatusRecord extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('employmentStatus')
            ->where('id', 1)
            ->update(['category' => 'PERMANENT']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('employmentStatus')
            ->where('id', 1)
            ->update(['category' => 'PROBATION']);
    }
}
