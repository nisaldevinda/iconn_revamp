<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNoticePeriodCalculationFunction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $script = 'CREATE OR REPLACE FUNCTION `noticePeriodCalculation` (currentEmploymentsId INT, currentJobsId INT) RETURNS VARCHAR(20) no sql deterministic
            BEGIN
                DECLARE _jobCategoryId INT;
                DECLARE _employmentStatusId INT;
                DECLARE _noticePeriod VARCHAR(20);

                SET _employmentStatusId = (SELECT employmentStatusId FROM employeeEmployment WHERE id = currentEmploymentsId);
                SET _jobCategoryId = (SELECT jobCategoryId FROM employeeJob WHERE id = currentJobsId);
                SET _noticePeriod = (SELECT IF (noticePeriod > 1, CONCAT_WS(" ", noticePeriod, noticePeriodUnit), CONCAT_WS(" ", noticePeriod, SUBSTRING(noticePeriodUnit FROM 1 FOR CHAR_LENGTH(noticePeriodUnit) - 1)))
                    FROM noticePeriodConfig
                    WHERE employmentStatusId = _employmentStatusId
                    AND jobCategoryId = _jobCategoryId LIMIT 1);

                RETURN _noticePeriod;
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
        DB::unprepared('DROP FUNCTION noticePeriodCalculation;');
    }
}
