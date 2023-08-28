<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsUneditableFieldsIntoEmploymentStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employmentStatus', function (Blueprint $table) {
            $table->boolean('isUneditable')->default(false)->after('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employmentStatus', function (Blueprint $table) {
            $table->dropColumn('isUneditable');
        });
    }
}
