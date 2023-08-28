<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertReligionData extends Migration
{
    public function up()
    {
        $religionData = array(
            [
                'id'=>1,
                'name' => 'Christianity',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>2,
                'name' => 'Hinduism',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>3,
                'name' => 'Islam',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>4,
                'name' => 'Buddhism',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>5,
                'name' => 'Judaism',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>6,
                'name' => 'Other',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
        );
        try {
            $record = DB::table('religion')->where('id', [1,2,3,4,5,6])->first();
            
            if ($record) {
                return('Religion does exist');
            }
            
            DB::table('religion')->insert($religionData);
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
            $record = DB::table('religion')->where('id', [1,2,3,4,5,6])->first();
        
            if (empty($record)) {
                return('Religion does not exist');
            }
      
            $affectedRows  = DB::table('religion')->where('id', [1,2,3,4,5,6])->delete();
      
            return ($affectedRows) ? true : false;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
