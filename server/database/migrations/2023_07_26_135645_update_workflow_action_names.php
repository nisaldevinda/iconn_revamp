<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateWorkflowActionNames extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('workflowAction')
            ->where('id', 2)
            ->update(['label' => 'Approve Request']);
        DB::table('workflowAction')
            ->where('id', 3)
            ->update(['label' => 'Reject Request']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('workflowAction')
            ->where('id', 2)
            ->update(['label' => 'Approve']);
        DB::table('workflowAction')
            ->where('id', 3)
            ->update(['label' => 'Reject']);
    }
}
