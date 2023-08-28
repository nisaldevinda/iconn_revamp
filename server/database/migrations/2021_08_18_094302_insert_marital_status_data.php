<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class InsertMaritalStatusData extends Migration
{
    public function up()
    {
        $maritalStatusData = array(
            [
                'id'=>1,
                'name' => 'Married',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>2,
                'name' => 'Single',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>3,
                'name' => 'Divorced',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id'=>4,
                'name' => 'Widowed',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
        );
        try {
            $record = DB::table('maritalStatus')->where('id', [1,2,3,4])->first();
            
            if ($record) {
                return('Marital Status does exist');
            }
            
            DB::table('maritalStatus')->insert($maritalStatusData);
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
            $record = DB::table('maritalStatus')->where('id', [1,2,3,4])->first();
        
            if (empty($record)) {
                return('Marital Status does not exist');
            }
        
            $affectedRows  = DB::table('maritalStatus')->where('id', [1,2,3,4])->delete();

            return ($affectedRows) ? true : false;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
