<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeStagingEmployeeTableTransformedObjectColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stagingEmployee', function (Blueprint $table) {
            $table->json('transformedObject')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stagingEmployee', function (Blueprint $table) {
            $table->json('transformedObject')->change();
        });
    }
}
