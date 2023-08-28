<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertworkflowAction extends Migration
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
                'actionName' => 'APPROVE',
                'isDelete' => false,
                'isPrimary'=> true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ]
        );

        try {
            $record = DB::table('workflowAction')->where('id', 1)->first();

            if ($record) {
                return('workflow action does exist');
            }

            DB::table('workflowAction')->insert($data);
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
            $record = DB::table('workflowAction')->where('id', 1)->first();

            if (empty($record)) {
                return('Workflow action does not exist');
            }

            $affectedRows  = DB::table('workflowAction')->where('id', 1)->delete();

            return ($affectedRows) ? true : false;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
