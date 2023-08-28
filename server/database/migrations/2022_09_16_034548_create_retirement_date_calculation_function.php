<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateRetirementDateCalculationFunction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $script = 'CREATE OR REPLACE FUNCTION `retirementDateCalculate` (dateOfBirth Date, hireDate Date) RETURNS Date no sql deterministic
            BEGIN
                DECLARE joiningAge INT;
                DECLARE minimumRetirementAge INT;
            
                SET joiningAge = TIMESTAMPDIFF(YEAR, dateOfBirth, hireDate);
            
                IF joiningAge >= 54 AND joiningAge < 55 THEN
                    SET minimumRetirementAge = 57;
            
                ELSEIF joiningAge >= 53 AND joiningAge < 54 THEN
                    SET minimumRetirementAge = 58;
            
                ELSEIF joiningAge >= 52 AND joiningAge < 53 THEN
                    SET minimumRetirementAge = 59;
            
                ELSE
                    SET minimumRetirementAge = 60;
            
                END IF;
            
                RETURN DATE_ADD(dateOfBirth, INTERVAL minimumRetirementAge YEAR);
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
        DB::unprepared('DROP FUNCTION retirementDateCalculate;');
    }
}
