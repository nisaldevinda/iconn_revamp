<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnCalledPermittedRolesToWorkflowStateTransitionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workflowStateTransitions', function (Blueprint $table) {
            $table->json('permittedRoles')->nullable()->default(null);
            
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
            $table->dropColumn('permittedRoles');
        });
    }
}
