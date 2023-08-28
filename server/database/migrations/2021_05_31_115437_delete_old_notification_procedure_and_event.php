<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DeleteOldNotificationProcedureAndEvent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $procedure = "CREATE PROCEDURE `deleteThirtyDaysOldNotifications`()
        BEGIN
            DELETE FROM notification WHERE createdAt < DATE_SUB(NOW(),INTERVAL 45 DAY);
        END;";

        $event = "CREATE EVENT deleteNotificationsEvent
        ON SCHEDULE EVERY 30 DAY
        STARTS '2021-05-10 00:00:00.000'
        ENDS '2025-02-28 00:00:00.000'
        ON COMPLETION NOT PRESERVE
        ENABLE
        DO call deleteThirtyDaysOldNotifications();";


        DB::unprepared("DROP PROCEDURE IF EXISTS `deleteThirtyDaysOldNotifications`;");
        DB::unprepared($procedure);

        DB::unprepared("DROP EVENT IF EXISTS `deleteNotificationsEvent`;");
        DB::unprepared($event);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
