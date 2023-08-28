<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertQualificationLevelData extends Migration
{
    public function up()
    {
        $qualificationLevelData = array(
            [
                'id'=>1,
                'name' => 'O-Level',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>2,
                'name' => 'A-Level',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>3,
                'name' => 'Diploma',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>4,
                'name' => 'Higher Diploma',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>5,
                'name' => 'Bachelor’s Degree',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>6,
                'name' => 'Bachelor’s Degree with Honours',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>7,
                'name' => 'Post-graduate Certificate',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>8,
                'name' => 'Post-graduate Diploma',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>9,
                'name' => 'Master’s degree',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>10,
                'name' => 'Doctoral degree',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
        );
        try {
            $record = DB::table('qualificationLevel')->where('id', [1,2,3,4,5,6,7,8,9,10])->first();
            
            if ($record) {
                return('Qualification Level does exist');
            }
            
            DB::table('qualificationLevel')->insert($qualificationLevelData);
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
            $record = DB::table('qualificationLevel')->where('id', [1,2,3,4,5,6,7,8,9,10])->first();
        
            if (empty($record)) {
                return('Qualification Level does not exist');
            }
      
            $affectedRows  = DB::table('qualificationLevel')->where('id', [1,2,3,4,5,6,7,8,9,10])->delete();
  
            return ($affectedRows) ? true : false;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
