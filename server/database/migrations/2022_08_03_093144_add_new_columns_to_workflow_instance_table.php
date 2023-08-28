<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToWorkflowInstanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workflowInstance', function (Blueprint $table) {
            $table->json('actionPermittedEmployees')->nullable()->default(null);
            $table->json('actionPermittedRoles')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workflowInstance', function (Blueprint $table) {
            $table->dropColumn('actionPermittedEmployees');
            $table->dropColumn('actionPermittedRoles');
        });
    }
}
