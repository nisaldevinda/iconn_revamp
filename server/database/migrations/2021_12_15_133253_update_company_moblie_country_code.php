<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCompanyMoblieCountryCode extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Migration for company phone feild
        DB::statement("
        UPDATE
            company as cmp
        SET
            cmp.phone = CONCAT('94','-',(select phone from company where id = cmp.id ))
        WHERE
            cmp.phone NOT LIKE '%-%';
        ");

        // Migration for company fax feild
        DB::statement("
        UPDATE
          company as cmp
        SET
          cmp.fax = CONCAT('94','-',(select fax from company where id = cmp.id ))
        WHERE
          cmp.fax NOT LIKE '%-%';
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
            // revert phone
            $phone = DB::statement("
                SELECT
                    id,
                    phone
                FROM
                    company
                WHERE
                phone LIKE '%-%';
            ");
            foreach ($phone as $row) {
                $updatedPhone = explode('-', $row->phone);
                DB::table('company')
                    ->where('id', $row->id)
                    ->update('phone', $updatedPhone);
            }

            // revert fax
            $fax = DB::statement("
                SELECT
                    id,
                    fax
                FROM
                    company
                WHERE
                fax LIKE '%-%';
            ");
            foreach ($fax as $row) {
                $updatedPhone = explode('-', $row->fax);
                DB::table('company')
                    ->where('id', $row->id)
                    ->update('fax', $updatedPhone);
            }
        } catch (Throwable $th) {
            throw $th;
        }
    }
}
