<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RenameSomeWorkflowActionLabelNames extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('workflowAction')->where('label', 'Cancel')->update(['label' => 'Cancel Request', 'isPrimary' => 1]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('workflowAction')->where('label', 'Cancel Request')->update(['label' => 'Cancel', 'isPrimary' => 0]);
    }
}
