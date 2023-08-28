<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertAttendenceTimeChangeWorkflowContext extends Migration
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
                'id'=> 3,
                'contextName' => 'Attendence Time Change',
                'isDelete' => false,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ]
        );

        try {
            $record = DB::table('workflowContext')->where('id', 3)->first();

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
            $record = DB::table('workflowContext')->where('id', 3)->first();

            if (empty($record)) {
                return('Workflow context does not exist');
            }

            $affectedRows  = DB::table('workflowContext')->where('id', 3)->delete();

            return ($affectedRows) ? true : false;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
