<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldNameColumnIntoFloderHierarchyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('folderHierarchy', function (Blueprint $table) {
            $table->string('fieldName')->after('parentId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('folderHierarchy', function (Blueprint $table) {
            $table->dropColumn('fieldName');
        });
    }
}
