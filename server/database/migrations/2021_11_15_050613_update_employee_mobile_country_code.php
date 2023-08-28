<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEmployeeMobileCountryCode extends Migration
{
    /**
     * Run the migrations to
     *  Update mobile nos with country code
     * @return void
     */
    public function up()
    {
        DB::statement("
            UPDATE
                employee as emp
            SET
                emp.mobilePhone = CONCAT('94','-',(select mobilePhone from employee where id = emp.id ))
            WHERE
                emp.mobilePhone NOT LIKE '%-%';
        ");
        DB::statement("
            UPDATE
                employee as emp
            SET
                emp.homePhone = CONCAT('94','-',(select homePhone from employee where id = emp.id ))
            WHERE
                emp.homePhone NOT LIKE '%-%';
        ");
        DB::statement("
            UPDATE
                employee as emp
            SET
                 emp.workPhone = CONCAT('94','-',(select workPhone from employee where id = emp.id ))
            WHERE
                emp.workPhone NOT LIKE '%-%';
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        try {
            // revert mobilePhone
            $mobilePhones = DB::statement("
                SELECT
                    id,
                    mobilePhone
                FROM
                    employee
                WHERE
                mobilePhone LIKE '%-%';
            ");
            foreach ($mobilePhones as $row) {
                $updatedPhone = explode('-', $row->mobilePhone);
                DB::table('employee')
                    ->where('id', $row->id)
                    ->update('mobilePhone', $updatedPhone);
            }

            // revert homePhone
            $homePhones = DB::statement("
                SELECT
                    id,
                    homePhone
                FROM
                    employee
                WHERE
                homePhone LIKE '%-%';
            ");
            foreach ($homePhones as $row) {
                $updatedPhone = explode('-', $row->homePhone);
                DB::table('employee')
                    ->where('id', $row->id)
                    ->update('homePhone', $updatedPhone);
            }

            // revert workPhone
            $workPhones = DB::statement("
                SELECT
                    id,
                    workPhone
                FROM
                    employee
                WHERE
                    workPhone LIKE '%-%';
            ");
            foreach ($workPhones as $row) {
                $updatedPhone = explode('-', $row->workPhone);
                DB::table('employee')
                    ->where('id', $row->id)
                    ->update('workPhone', $updatedPhone);
            }
        } catch (Throwable $th) {
            throw $th;
        }
    }
}
