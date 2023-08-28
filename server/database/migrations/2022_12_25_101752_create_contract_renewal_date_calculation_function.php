<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractRenewalDateCalculationFunction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $script = 'CREATE OR REPLACE FUNCTION `contractRenewalDateCalculate` (currentJobsId INT) RETURNS VARCHAR(20) no sql deterministic
            BEGIN
                DECLARE _employmentStatusId INT;
                DECLARE _employmentStatusPeriodUnit VARCHAR(20);
                DECLARE _contractRenewalDate VARCHAR(20);

                SET _employmentStatusId = (SELECT employmentStatusId FROM employeeJob WHERE id = currentJobsId);

                IF _employmentStatusId = 1 THEN
                    SET _contractRenewalDate = (SELECT effectiveDate FROM employeeJob WHERE id = currentJobsId);
                ELSE
                    SET _employmentStatusPeriodUnit = (SELECT periodUnit FROM employmentStatus WHERE id = _employmentStatusId);

                    IF _employmentStatusPeriodUnit = "YEARS" THEN
                        SET _contractRenewalDate = (SELECT DATE_ADD(employeeJob.effectiveDate, INTERVAL employmentStatus.period YEAR)
                            FROM employeeJob 
                            JOIN employmentStatus
                            ON employeeJob.employmentStatusId = employmentStatus.id
                            WHERE employeeJob.id = currentJobsId);
                    ELSEIF _employmentStatusPeriodUnit = "MONTHS" THEN
                        SET _contractRenewalDate = (SELECT DATE_ADD(employeeJob.effectiveDate, INTERVAL employmentStatus.period MONTH)
                            FROM employeeJob 
                            JOIN employmentStatus
                            ON employeeJob.employmentStatusId = employmentStatus.id
                            WHERE employeeJob.id = currentJobsId);
                    ELSE
                        SET _contractRenewalDate = (SELECT DATE_ADD(employeeJob.effectiveDate, INTERVAL employmentStatus.period DAY)
                            FROM employeeJob 
                            JOIN employmentStatus
                            ON employeeJob.employmentStatusId = employmentStatus.id
                            WHERE employeeJob.id = currentJobsId);
                    END IF;
                END IF;

                RETURN DATE_FORMAT(_contractRenewalDate, "%d-%m-%Y");
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
        DB::unprepared('DROP FUNCTION contractRenewalDateCalculate;');
    }
}
