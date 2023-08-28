<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToWorkflowStateTransitionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workflowStateTransitions', function (Blueprint $table) {
            $table->json('permittedEmployees')->nullable()->default(null);
            $table->enum('permissionType', ['ROLE_BASE', 'EMPLOYEE_BASE'])->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workflowStateTransitions', function (Blueprint $table) {
            $table->dropColumn('permittedEmployees');
            $table->dropColumn('permissionType');
        });
    }
}
