<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFullnameFeildEmployee extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee', function ($table) {
            if (Schema::hasColumn('employee', 'forename')) {
                $table->dropColumn('forename');
            }

            if (!Schema::hasColumn('employee', 'fullName')) {
                $table->string('fullName')->after('maidenName');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee', function ($table) {
            if (Schema::hasColumn('employee', 'fullName')) {
                $table->dropColumn('fullName');
            }

            if (!Schema::hasColumn('employee', 'forename')) {
                $table->string('forename')->after('maidenName');
            }
        });
    }
}
