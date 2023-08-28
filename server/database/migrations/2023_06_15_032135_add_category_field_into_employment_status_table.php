<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCategoryFieldIntoEmploymentStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employmentStatus', function(Blueprint $table) {
            $table->enum('category', ['PROBATION', 'CONTRACT', 'PERMANENT'])->default('PROBATION')->after('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employmentStatus', function(Blueprint $table) {
            $table->dropColumn('category');
        });
    }
}
