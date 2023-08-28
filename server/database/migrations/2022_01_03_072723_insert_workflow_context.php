<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertWorkflowContext extends Migration
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
                'contextName' => 'Profile Update',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ]
        );

        try {
            $record = DB::table('workflowContext')->where('id', 1)->first();

            if ($record) {
                return('workflow context does exist');
            }

            DB::table('workflowContext')->insert($data);
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
            $record = DB::table('workflowContext')->where('id', 1)->first();

            if (empty($record)) {
                return('Workflow context does not exist');
            }

            $affectedRows  = DB::table('workflowContext')->where('id', 1)->delete();

            return ($affectedRows) ? true : false;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    
}
