<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateLeaveAccrualProcessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leaveAccrualProcess', function (Blueprint $table) {
            $table->id();
            $table->integer('leaveAccrualId');
            $table->enum('method', ['AUTOMATE', 'MANUAL'])->default('AUTOMATE');
            $table->integer('numberOfAllocatedEntitlements')->default(0);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leaveAccrualProcess');
    }
}
