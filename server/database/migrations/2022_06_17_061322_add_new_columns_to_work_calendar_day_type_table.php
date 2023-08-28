<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToWorkCalendarDayTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workCalendarDayType', function (Blueprint $table) {
            $table->string('shortCode')->nullable()->default(null)->after('name');
            $table->boolean('isDelete')->default(false);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
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
        Schema::table('workCalendarDayType', function (Blueprint $table) {
            $table->dropColumn('shortCode');
            $table->dropColumn('createdBy');
            $table->dropColumn('updatedBy');
            $table->dropColumn('createdAt');
            $table->dropColumn('updatedAt');
        });
    }
}
