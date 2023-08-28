<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateNewTableCalledLeaveCoveringPersonRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leaveCoveringPersonRequests', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('leaveRequestId')->nullable()->default(null);
            $table->integer('coveringEmployeeId')->nullable()->default(null);
            $table->enum('state', ['PENDING', 'APPROVED' ,'DECLINED', 'CANCELED']);
            $table->string('comment')->nullable()->default(null);
            $table->boolean('isDelete')->default(false);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
            $table->timestamp('approvedAt')->nullable()->default(null);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leaveCoveringPersonRequests');
    }
}
