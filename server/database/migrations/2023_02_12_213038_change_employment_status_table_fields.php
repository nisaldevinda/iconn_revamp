<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeEmploymentStatusTableFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employmentStatus', function (Blueprint $table) {
            $table->dropColumn('title');
            $table->string('name')->change();
            $table->boolean('allowEmploymentPeriod')->default(false)->after('name');
            $table->enum('notificationPeriodUnit', ['YEARS', 'MONTHS', 'DAYS'])->nullable()->after('periodUnit');
            $table->integer('notificationPeriod')->nullable()->after('periodUnit');
            $table->boolean('enableEmailNotification')->default(false)->after('periodUnit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employmentStatus', function (Blueprint $table) {
            $table->string('title')->after('id');
            $table->dropColumn('allowEmploymentPeriod');
            $table->dropColumn('enableEmailNotification');
            $table->dropColumn('notificationPeriodUnit');
            $table->dropColumn('notificationPeriod');
        });
    }
}
