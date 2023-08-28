<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGenarateStatusFunction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $script = 'CREATE OR REPLACE FUNCTION `genarateStatus` (isActive boolean) RETURNS VARCHAR(20) no sql deterministic
            BEGIN
                IF isActive = true THEN
                    RETURN "Active";
                ELSE
                    RETURN "Inactive";
                END IF;
            END;';

        DB::unprepared($script);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP FUNCTION genarateStatus;');
    }
}
