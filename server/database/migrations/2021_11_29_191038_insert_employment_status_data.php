<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertEmploymentStatusData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $data = array(
            [
                'id'=> 1,
                'name' => 'Terminated',
                'isDelete' => false,
                'isUneditable' => true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ]
        );

        try {
            $record = DB::table('employmentStatus')->where('id', 1)->first();

            if ($record) {
                return('Employment Status does exist');
            }

            DB::table('employmentStatus')->insert($data);
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
            $record = DB::table('employmentStatus')->where('id', 1)->first();

            if (empty($record)) {
                return('Employment Status does not exist');
            }

            $affectedRows  = DB::table('employmentStatus')->where('id', 1)->delete();

            return ($affectedRows) ? true : false;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
