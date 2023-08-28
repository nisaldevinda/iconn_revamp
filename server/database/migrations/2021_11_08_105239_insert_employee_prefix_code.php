<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertEmployeePrefixCode extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $employeePrefixCode =  array(
            [
                'id' => 1,
                'controlName' => 'EmployeeController',
                'prefix' => "EMP",
                'length' => '6',
                'nextNo' => '0',
                'isDelete' => 0,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP')
            ]
        );

        try {
            $record = DB::table('prefixCode')->where('id', 1)->first();

            if ($record) {
                return ('Prefix code exists');
            }

            DB::table('prefixCode')->insert($employeePrefixCode);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        try {
            $record = DB::table('prefixCode')->where('id', 1)->first();

            if (empty($record)) {
                return ('Company does not exist');
            }

            $affectedRows = DB::table('prefixCode')->where('id', 1)->delete();

            return ($affectedRows) ? true : false;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
