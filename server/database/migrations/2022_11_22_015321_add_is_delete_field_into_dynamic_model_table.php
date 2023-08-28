<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsDeleteFieldIntoDynamicModelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dynamicModel', function (Blueprint $table) {
            $table->boolean('isDelete')->default(false);
            $table->boolean('isUnpublished')->default(false);
            $table->string('description')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dynamicModel', function (Blueprint $table) {
            $table->dropColumn('isDelete');
            $table->dropColumn('isUnpublished');
            $table->dropColumn('description');
        });
    }
}
