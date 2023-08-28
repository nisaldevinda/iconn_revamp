<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateLeavePeriodTypeInLeaveRequestDetailTableAccordingToLeaveRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $leaveRequests = DB::table('leaveRequest')->get();

        if (!is_null($leaveRequests)) {

            foreach ($leaveRequests as $key => $value) {
                $value = (array) $value;

                $leaveRequestDetail = DB::table('leaveRequestDetail')->where('leaveRequestId', $value['id'])->get();

                if (!is_null($leaveRequestDetail)) {
                    DB::table('leaveRequestDetail')
                    ->where('leaveRequestId', $value['id'])
                    ->update(['leavePeriodType' => $value['leavePeriodType']]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $leaveRequests = DB::table('leaveRequest')->get();

        if (!is_null($leaveRequests)) {

            foreach ($leaveRequests as $key => $value) {
                $value = (array) $value;

                $leaveRequestDetail = DB::table('leaveRequestDetail')->where('leaveRequestId', $value['id'])->get();

                if (!is_null($leaveRequestDetail)) {
                    DB::table('leaveRequestDetail')
                    ->where('leaveRequestId', $value['id'])
                    ->update(['leavePeriodType' => 'FULL_DAY']);
                }
            }
        }
    }
}
