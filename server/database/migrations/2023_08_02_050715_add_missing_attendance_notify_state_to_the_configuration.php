<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingAttendanceNotifyStateToTheConfiguration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $configuration = [
            "id" => 15,
            "key" => "missing_attendance_email_notify_state",
            "description" => "Configuration For Handle Missing Attendance Email Notifications",
            "type" => "boolean",
            "value" => 'false'
        ];

        $configAvailabilty = DB::table('configuration')->where('id', '=', 15)->first();

        if (Schema::hasTable('configuration') && is_null($configAvailabilty)) {
            DB::table('configuration')->insert($configuration);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('the_configuration', function (Blueprint $table) {
            DB::table('configuration')->where('id', '=', 15)->delete();
        });
    }
}
