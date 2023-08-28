<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertDefaultBaseDayTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $defaultBasDayTypeData = array(
            [
                'id'=> 1,
                'name' => 'Working Day',
                'isActive' => true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            [
                'id'=> 2,
                'name' => 'Non Working Day',
                'isActive' => true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            [
                'id'=> 3,
                'name' => 'Statutory Holiday',
                'isActive' => true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            [
                'id'=> 4,
                'name' => 'Poya Day',
                'isActive' => true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ]
        );

        try {

            //insert default base day types
            foreach ($defaultBasDayTypeData as $baseDayTypeKey => $defaultBasDayType) {
               $defaultBasDayType = (array) $defaultBasDayType;
              
               $record = DB::table('baseDayType')->where('id', $defaultBasDayType['id'])->first();
               
               if (is_null($record)) {
                   DB::table('baseDayType')->insert($defaultBasDayType);
               }
           }
           
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
        $defaultBasDayTypeData = array(
            [
                'id'=> 1,
                'name' => 'Working Day',
                'isActive' => true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            [
                'id'=> 2,
                'name' => 'Non Working Day',
                'isActive' => true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            [
                'id'=> 3,
                'name' => 'Statutory Holiday',
                'isActive' => true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ],
            [
                'id'=> 4,
                'name' => 'Poya Day',
                'isActive' => true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                'isReadOnly' => true
            ]
        );

        try {

            //insert default base day types
            foreach ($defaultBasDayTypeData as $baseDayTypeKey => $defaultBasDayType) {
               $defaultBasDayType = (array) $defaultBasDayType;
              
               $record = DB::table('baseDayType')->where('id', $defaultBasDayType['id'])->first();
               
               if (!is_null($record)) {
                   DB::table('baseDayType')->where('id',$defaultBasDayType['id'])->delete();
               }
           }
           
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
