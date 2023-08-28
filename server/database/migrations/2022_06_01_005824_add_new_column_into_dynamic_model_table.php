<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnIntoDynamicModelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dynamicModel', function (Blueprint $table) {
            $table->string('title')->after('modelName');
            $table->boolean('isDynamicMasterDataModel')->default(false)->after('title');
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
            $table->dropColumn('title');
            $table->dropColumn('isDynamicMasterDataModel');
        });
    }
}
