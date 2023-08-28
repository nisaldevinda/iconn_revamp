<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableCalledWorkflowInstanceApprovalLevelDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workflowInstanceApprovalLevelDetail', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('workflowInstanceApproverLevelId')->nullable()->default(null);
            $table->unsignedBigInteger('performAction')->nullable()->default(null);
            $table->string('approverComment')->nullable()->default(null);
            $table->integer('performBy')->nullable()->default(null);
            $table->timestamp('performAt')->default(null);

            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('workflowInstanceApprovalLevelDetail');
    }
}
