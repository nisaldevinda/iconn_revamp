<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameCompetencyColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('competency', function(Blueprint $table) {
            $table->renameColumn('competencyType', 'competencyTypeId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('competency', function(Blueprint $table) {
            $table->renameColumn('competencyTypeId', 'competencyType');
        });
    }
}
