<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAudienceTypeToDocumentManagerFileTabe extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('documentManagerFile', function (Blueprint $table) {
            $table->enum('audienceMethod', ['ALL', 'REPORT_TO', 'QUERY', 'CUSTOM'])->nullable()->default(null)->after('systemAlertNotification');
            $table->json('audienceData')->default('{}')->after('audienceMethod');
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
            $table->dropColumn('audienceMethod');
            $table->dropColumn('audienceData');
        });
    }
}
