<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSuccessAndFailureStatesColumnsToWorkflowDefineTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workflowDefine', function (Blueprint $table) {
            $table->json('sucessStates')->default("[]");
            $table->json('failureStates')->default("[]");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workflowDefine', function (Blueprint $table) {
            $table->dropColumn('sucessStates');
            $table->dropColumn('failureStates');
        });
    }
}
