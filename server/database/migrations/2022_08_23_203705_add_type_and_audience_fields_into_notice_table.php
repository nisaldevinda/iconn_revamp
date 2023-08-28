<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeAndAudienceFieldsIntoNoticeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notice', function (Blueprint $table) {
            $table->enum('type', ['COMPANY_NOTICES', 'TEAM_NOTICES'])
                ->default('COMPANY_NOTICES')
                ->after('status');
            $table->enum('audienceMethod', ['ALL', 'ASSIGNED_TO_ME', 'REPORT_TO', 'QUERY', 'CUSTOM'])
                ->default('ALL')
                ->after('type');
            $table->json('audienceData')
                ->default('{}')
                ->after('audienceMethod');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notice', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('audienceMethod');
            $table->dropColumn('audienceData');
        });
    }
}
