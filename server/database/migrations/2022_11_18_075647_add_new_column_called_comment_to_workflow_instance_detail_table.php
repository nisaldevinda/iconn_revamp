<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnCalledCommentToWorkflowInstanceDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workflowInstanceDetail', function (Blueprint $table) {
            $table->string('approverComment')->nullable()->default(null)->after('performUserId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('workflowInstanceDetail', function (Blueprint $table) {
            $table->dropColumn('approverComment');
        });
    }
}
