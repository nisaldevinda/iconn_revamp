<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateSystemAdminWorkflowPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('userRole')->where('id', 2)->update(['workflowManagementActions' => json_encode([])]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('userRole')->where('id', 2)->update(['workflowManagementActions' => json_encode(['*'])]);
    }
}
