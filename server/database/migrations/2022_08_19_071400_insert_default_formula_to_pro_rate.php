<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertDefaultFormulaToProRate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $defaultFormula = [
            'firstQuater' => 14,
            'secondQuater' => 10,
            'thirdQuater' => 7,
            'forthQuater' => 4
        ];

        $dataSet = array(
            [
                'id'=> 1,
                'name' => 'Default Pro Rate Formula',
                'formulaDetail' => json_encode($defaultFormula),
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ]
        );

        try {

            foreach ($dataSet as $key => $data) {
               
                $record = DB::table('proRateFormula')->where('id', $data['id'])->first();
    
                if ($record) {
                    return('prorate formula does exist');
                }
    
                DB::table('proRateFormula')->insert($data);
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
        DB::table('proRateFormula')->where('id', [1])->delete();
    }
}
