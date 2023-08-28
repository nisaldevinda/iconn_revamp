<?php

use Illuminate\Database\Migrations\Migration;

class InsertCompanyInfoData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $companyData =  array(
            [
            'id'=>1,
            'name' => 'Emageia',
            'taxId' => "1234566790",
            'registrationNo' => '10001',
            'phone' => '0115146699',   
            'fax' => '0115146699',
            'email' => 'info@emageia.com',
            'street1' => '111, T.B. Jayah Mawatha, Colombo 10.',
            'street2' => '',
            'city' => 'Colombo 10' ,
            'stateProvince' => "Western Province",
            'zipCode' => '00700',
            'country' => "Sri Lanka",
            'timeZone' => "Asia/Colombo",
            'notes' => "",
            'isDelete' => 0,
            'createdBy' => null,
            'updatedBy' => null,
            'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
            'updatedAt' => DB::raw('CURRENT_TIMESTAMP')
            ]);
        
        try {
            $record = DB::table('company')->where('id', 1)->first();
            
            if ($record) {
                return('Company does exist');
            }
            
            DB::table('company')->insert($companyData);
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
            $record = DB::table('company')->where('id', 1)->first();
        
            if (empty($record)) {
                return('Company does not exist');
            }
        
            $affectedRows = DB::table('company')->where('id', 1)->delete();
        
            return ($affectedRows) ? true : false;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
