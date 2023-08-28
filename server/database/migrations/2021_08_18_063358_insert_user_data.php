<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class InsertUserData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $userData =  array(
            [
            'id'=>1,
            'email' => 'admin@emageia.com',
            'emailVerified' => true,
            'firstName' => 'matt',
            'middleName' => 'Tom',
            'lastName' => 'admin@emageia.com',
            'nickname' => 'admin@emageia.com',
            'password' => '$2y$10$GzKpD.TTNWU88aEPuqvD4OsNyxnb1kyqclKh9JU4kKfGUhnWwwXBi', // password = 123
            'inactive' => false,
            'blocked' => false,
            'expired' => false,
            'lastIp' => '1.2.3.4',
            'lastLogin' => null,
            'lastFailedLogin' => null,
            'lastPasswordReset' => null,
            'loginsCount' => 1,
            'failedLoginsCount' => 1,
            'multifactor' => '1dw',
            'phoneNumber' => '0778355332',
            'phoneVerified' => true,
            'picture' => 'Tom',
            'identities' =>'{"key1":"value1","key2":"value2"}',
            'userMetadata' => '{"key1":"value1","key2":"value2"}',
            'createdBy' => null,
            'updatedBy' => null,
            'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
            'updatedAt' => DB::raw('CURRENT_TIMESTAMP')
            ]);
        
        try {
            $record = DB::table('user')->where('id', 1)->first();
            
            if ($record) {
                return('User does exist');
            }
            
            DB::table('user')->insert($userData);
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
            $record = DB::table('user')->where('id', 1)->first();
        
            if (empty($record)) {
                return('User does not exist');
            }
        
            $affectedRows = DB::table('user')->where('id', 1)->delete();
        
            return ($affectedRows) ? true : false;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
