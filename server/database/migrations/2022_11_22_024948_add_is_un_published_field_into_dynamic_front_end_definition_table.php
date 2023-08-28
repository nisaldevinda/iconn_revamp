<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsUnPublishedFieldIntoDynamicFrontEndDefinitionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('frontEndDefinition', function (Blueprint $table) {
            $table->boolean('isUnpublished')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('frontEndDefinition', function (Blueprint $table) {
            $table->dropColumn('isUnpublished');
        });
    }
}
