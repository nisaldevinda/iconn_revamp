<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCategoryIdFieldIntoNoticeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('notice', 'noticeCategoryId')) {
            Schema::table('notice', function (Blueprint $table) {
                $table->integer('noticeCategoryId')->nullable()->default(null)->after('topic');
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
        if (Schema::hasColumn('notice', 'noticeCategoryId')) {
            Schema::table('notice', function (Blueprint $table) {
                $table->dropColumn('noticeCategoryId');
            });
        }
    }
}
