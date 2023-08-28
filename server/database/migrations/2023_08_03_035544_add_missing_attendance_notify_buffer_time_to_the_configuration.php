<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingAttendanceNotifyBufferTimeToTheConfiguration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $configuration = [
            "id" => 16,
            "key" => "missing_attendance_email_notify_buffer_time",
            "description" => "Configuration For Missing Attendance Email Notifications Buffer Time",
            "type" => "numeric",
            "value" => "120"
        ];

        $configAvailabilty = DB::table('configuration')->where('id', '=', 16)->first();

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
            DB::table('configuration')->where('id', '=', 16)->delete();
        });
    }
}
