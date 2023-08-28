<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableCalledWorkflowInstanceApprovalLevel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workflowInstanceApprovalLevel', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('workflowInstanceId')->nullable()->default(null);
            $table->integer('levelSequence')->nullable()->default(null);
            $table->enum('levelStatus', ['PENDING', 'APPROVED', 'REJECTED', 'CANCELED']);
            $table->boolean('isLevelCompleted')->default(false);
            $table->boolean('isHaveNextLevel')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('workflowInstanceApprovalLevel');
    }
}
