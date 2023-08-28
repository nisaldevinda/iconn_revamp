<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WorkflowRolePermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workflow_role_permission', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('roleId')->nullable()->default(null);
            $table->json('actionId')->nullable()->default(null);
           // $table->foreign('postState')->references('id')->on('workflow_state');
           $table->boolean('isDelete')->default(false);
           $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            //$table->foreign('actionId')->references('id')->on('workflow_actions');
           
        });    
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
