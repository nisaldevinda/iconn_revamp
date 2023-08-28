<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContentTypeAndAttachmentFieldsIntoNoticeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('notice', 'contentType')) {
            Schema::table('notice', function (Blueprint $table) {
                $table->enum('contentType', ['TEXT', 'ATTACHMENT'])->default('TEXT')->after('type');
            });
        }

        if (!Schema::hasColumn('notice', 'attachmentId')) {
            Schema::table('notice', function (Blueprint $table) {
                $table->integer('attachmentId')->nullable()->after('description');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('notice', 'contentType')) {
            Schema::table('notice', function (Blueprint $table) {
                $table->dropColumn('contentType');
            });
        }

        if (!Schema::hasColumn('notice', 'attachmentId')) {
            Schema::table('notice', function (Blueprint $table) {
                $table->dropColumn('attachmentId');
            });
        }
    }
}
