<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveYearColumnFromWorkCalandarTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('workCalendar', function (Blueprint $table) {
            $table->dropColumn('year');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('workCalendar', function (Blueprint $table) {
            $table->string('year')->nullable()->default(null);
        });
        
        
    }
}
