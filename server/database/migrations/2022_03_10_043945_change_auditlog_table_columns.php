<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeAuditlogTableColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('auditLog', function ($table) {

            $table->json('previousState')->nullable()->change();
            $table->dropColumn('createdBy');
            $table->dropColumn('updatedBy');
            $table->dropColumn('createdAt');
            $table->dropColumn('updatedAt');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('auditLog', function ($table) {

            $table->integer('createdBy');
            $table->integer('updatedBy');
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }
}
