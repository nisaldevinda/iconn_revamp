<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeEmployeeProfileColumnNameOnEmployeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $hasProfilePicture = Schema::hasColumn('employee', 'profilePicture');
        $hasProfilePictureId = Schema::hasColumn('employee', 'profilePictureId');

        if ($hasProfilePictureId) {
            Schema::table('employee', function($table) {
                $table->dropColumn('profilePictureId');
            });
        }
        
        if (!$hasProfilePicture) {
            Schema::table('employee', function($table) {
                $table->integer('profilePicture')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('employee', 'profilePicture')) {
            Schema::table('employee', function($table) {
                $table->integer('profilePictureId')->nullable();
            });
        }
    }
}
