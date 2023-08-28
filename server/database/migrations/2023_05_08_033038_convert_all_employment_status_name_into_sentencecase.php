<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ConvertAllEmploymentStatusNameIntoSentenceCase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('employmentStatus') && Schema::hasColumn('employmentStatus', 'name')) {
            DB::table('employmentStatus')
                ->update([
                    'name' => DB::raw("CONCAT_WS(' - ',
                    CONCAT(UPPER(LEFT(name, 1)), LOWER(RIGHT(name, LENGTH(name) - 1))),
                    IF(period IS NOT NULL AND periodUnit IS NOT NULL,
                        CONCAT(period, ' ', CONCAT(UPPER(LEFT(periodUnit, 1)), LOWER(RIGHT(periodUnit, LENGTH(periodUnit) - 1)))),
                        NULL
                    ))")
                ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('employmentStatus') && Schema::hasColumn('employmentStatus', 'name')) {
            DB::table('employmentStatus')
                ->update([
                    'name' => DB::raw("UPPER(SUBSTRING_INDEX(name, ' - ', 1))")
                ]);
        }
    }
}
