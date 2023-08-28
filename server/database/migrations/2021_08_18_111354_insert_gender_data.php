<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertGenderData extends Migration
{
    public function up()
    {
        $genderData = array(
            [
                'id'=>1,
                'name' => 'Male',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>2,
                'name' => 'Female',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>3,
                'name' => 'Others',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ]
        );
        try {
            $record = DB::table('gender')->where('id', [1,2,3,4])->first();
            
            if ($record) {
                return('Gender does exist');
            }
            
            DB::table('gender')->insert($genderData);
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
            $record = DB::table('gender')->where('id', [1,2,3,4])->first();
        
            if (empty($record)) {
                return('Gender does not exist');
            }
        
            $affectedRows  = DB::table('gender')->where('id', [1,2,3,4])->delete();
  

            return ($affectedRows) ? true : false;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
