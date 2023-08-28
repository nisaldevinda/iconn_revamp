<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeTypeOfStatusColumnInLeaveRequestDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaveRequestDetail', function (Blueprint $table) {
            DB::statement("ALTER TABLE leaveRequestDetail MODIFY status enum('pending', 'approved', 'reject', 'cancel', 'PENDING', 'APPROVED', 'REJECTED', 'CANCELED') NOT NULL;");
            DB::statement("UPDATE `leaveRequestDetail` set `status` = 'PENDING' where `status` = 'pending';");
            DB::statement("UPDATE `leaveRequestDetail` set `status` = 'APPROVED' where `status` = 'approved';");
            DB::statement("UPDATE `leaveRequestDetail` set `status` = 'REJECTED' where `status` = 'reject';");
            DB::statement("UPDATE `leaveRequestDetail` set `status` = 'CANCELED' where `status` = 'cancel';");
            DB::statement("ALTER TABLE leaveRequestDetail MODIFY status enum('PENDING', 'APPROVED', 'REJECTED', 'CANCELED') NOT NULL;");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leaveRequestDetail', function (Blueprint $table) {
            DB::statement("ALTER TABLE leaveRequestDetail MODIFY status enum('pending', 'approve', 'reject', 'cancel', 'PENDING', 'APPROVED', 'REJECTED', 'CANCELED') NOT NULL;");
            DB::statement("UPDATE `leaveRequestDetail` set `status` = 'pending' where `status` = 'PENDING';");
            DB::statement("UPDATE `leaveRequestDetail` set `status` = 'approved' where `status` = 'APPROVED';");
            DB::statement("UPDATE `leaveRequestDetail` set `status` = 'reject' where `status` = 'REJECTED';");
            DB::statement("UPDATE `leaveRequestDetail` set `status` = 'cancel' where `status` = 'CANCELED';");
            DB::statement("ALTER TABLE leaveRequestDetail MODIFY status enum('pending', 'approved', 'reject', 'cancel') NOT NULL;");
        });
    }
}
