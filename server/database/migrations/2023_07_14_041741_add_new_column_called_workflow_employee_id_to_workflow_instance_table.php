<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnCalledWorkflowEmployeeIdToWorkflowInstanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workflowInstance', function (Blueprint $table) {
            $table->integer('workflowEmployeeId')->nullable()->default(null);
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
            $table->dropColumn('workflowEmployeeId');
        });
    }
}
