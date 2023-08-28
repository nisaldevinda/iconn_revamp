<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrganizationLevelConfigurationAndThemeConfiguration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company', function (Blueprint $table) {
            $table->string('primaryColor')->after('rootEmployeeId')->default("#86C129");
            $table->string('textColor')->after('primaryColor')->default("#000066");
            $table->integer('iconFileObjectId')->after('textColor')->nullable()->default(null);
            $table->integer('coverFileObjectId')->after('iconFileObjectId')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company', function (Blueprint $table) {
            $table->dropColumn('primaryColor');
            $table->dropColumn('textColor');
            $table->dropColumn('iconFileObjectId');
            $table->dropColumn('coverFileObjectId');
        });
    }
}
