<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsForCoveringPersonToLeaveType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaveType', function (Blueprint $table) {
            $table->boolean('allowCoveringPerson')->default(false)->after('whoCanAssign');
            $table->json('whoCanUseCoveringPerson')->nullable()->default(null)->after('allowCoveringPerson');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leaveType', function (Blueprint $table) {
            $table->dropColumn('allowCoveringPerson');
            $table->dropColumn('whoCanUseCoveringPerson');
        });
    }
}
