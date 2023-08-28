<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRehiringRelatedFieldsIntoResignationTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resignationType', function (Blueprint $table) {
            $table->boolean('allowedToRehire')->default(false)->after('name');
            $table->integer('reactivateAllowedPeriod')->nullable()->default(null)->after('name');
            $table->enum('reactivateAllowedPeriodUnit', ['YEAR', 'MONTH', 'DAY'])->nullable()->default(null)->after('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('resignationType', function (Blueprint $table) {
            $table->dropColumn('allowedToRehire');
            $table->dropColumn('reactivateAllowedPeriod');
            $table->dropColumn('reactivateAllowedPeriodUnit');
        });
    }
}
