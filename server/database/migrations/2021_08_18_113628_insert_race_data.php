<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertRaceData extends Migration
{
    public function up()
    {
        $raceData = array(
            [
                'id'=>1,
                'name' => 'Hispanic or Latino',
                'isDelete' => false,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>2,
                'name' => 'White',
                'isDelete' => false,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>3,
                'name' => 'Black or African American',
                'isDelete' => false,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>4,
                'name' => 'American Indian or Alaska Native',
                'isDelete' => false,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>5,
                'name' => 'Asian',
                'isDelete' => false,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>6,
                'name' => 'Asian',
                'isDelete' => false,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
        );
        try {
            $record = DB::table('race')->where('id', [1,2,3,4,5,6])->first();
            
            if ($record) {
                return ('Race does exist');
            }
            
            DB::table('race')->insert($raceData);
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
            $record = DB::table('race')->where('id', [1,2,3,4,5,6])->first();
        
            if (empty($record)) {
                return ('Race does not exist');
            }
      
            $affectedRows  =  DB::table('race')->where('id', [1,2,3,4,5,6])->delete();
  
            return ($affectedRows) ? true : false;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
