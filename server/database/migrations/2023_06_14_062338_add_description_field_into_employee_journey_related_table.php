<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescriptionFieldIntoEmployeeJourneyRelatedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promotionType', function (Blueprint $table) {
            if (!Schema::hasColumn('promotionType', 'description')) {
                $table->string('description')->nullable()->default(null);
            }
        });

        Schema::table('confirmationReason', function (Blueprint $table) {
            if (!Schema::hasColumn('confirmationReason', 'description')) {
                $table->string('description')->nullable()->default(null);
            }
        });

        Schema::table('transferType', function (Blueprint $table) {
            if (!Schema::hasColumn('transferType', 'description')) {
                $table->string('description')->nullable()->default(null);
            }
        });

        Schema::table('resignationType', function (Blueprint $table) {
            if (!Schema::hasColumn('resignationType', 'description')) {
                $table->string('description')->nullable()->default(null);
            }
        });

        Schema::table('terminationReason', function (Blueprint $table) {
            if (!Schema::hasColumn('terminationReason', 'description')) {
                $table->string('description')->nullable()->default(null);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promotionType', function (Blueprint $table) {
            if (Schema::hasColumn('promotionType', 'description')) {
                $table->dropColumn('description');
            }
        });

        Schema::table('confirmationReason', function (Blueprint $table) {
            if (Schema::hasColumn('confirmationReason', 'description')) {
                $table->dropColumn('description');
            }
        });

        Schema::table('transferType', function (Blueprint $table) {
            if (Schema::hasColumn('transferType', 'description')) {
                $table->dropColumn('description');
            }
        });

        Schema::table('resignationType', function (Blueprint $table) {
            if (Schema::hasColumn('resignationType', 'description')) {
                $table->dropColumn('description');
            }
        });

        Schema::table('terminationReason', function (Blueprint $table) {
            if (Schema::hasColumn('terminationReason', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
}
