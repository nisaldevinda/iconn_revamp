<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAcknowledgmentFieldsdocumentManagerFileTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('documentManagerFile', function (Blueprint $table) {
            $table->dropColumn('employeeId');
            $table->date('deadline')->nullable()->default(null)->after('fileId');
            $table->boolean('hasRequestAcknowledgement')->default(false)->after('deadline');
            $table->boolean('hasFilePermission')->default(false)->after('hasRequestAcknowledgement');
            $table->boolean('emailNotification')->default(false)->after('hasFilePermission');
            $table->boolean('systemAlertNotification')->default(false)->after('emailNotification');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('documentManagerFile', function (Blueprint $table) {
            $table->integer('employeeId')->nullable()->default(null);
            $table->dropColumn('deadline');
            $table->dropColumn('hasRequestAcknowledgement');
            $table->dropColumn('hasFilePermission');
            $table->dropColumn('emailNotification');
            $table->dropColumn('systemAlertNotification');
        });
    }
}
