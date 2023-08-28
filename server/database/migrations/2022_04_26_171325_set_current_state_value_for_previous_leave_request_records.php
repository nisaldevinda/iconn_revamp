<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class SetCurrentStateValueForPreviousLeaveRequestRecords extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        try {
            $workflowInstanceList =  DB::table('workflowInstance')->leftJoin('workflowDetail', 'workflowDetail.instanceId', '=', 'workflowInstance.id')->where('contextId', '2')->get();

            if (!is_null($workflowInstanceList)) {

                foreach ($workflowInstanceList as $key => $value) {
                    $value = (array) $value;
                    $detail = (array) json_decode($value['details']);
                    $leaveRequestId = $detail['id'];
                    $currentState = $value['priorState'];

                    if (!empty($leaveRequestId) && !empty($currentState)) {
                        DB::table('leaveRequest')->where('id', $leaveRequestId)->update(['currentState' => $currentState]);
                    }
                
                }
            }

            DB::table('leaveRequest')->whereNull('workflowInstanceId')->update(['currentState' => '2']);
        } catch (\Throwable $th) {
            throw $th;
        }
        
    
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $currentState = NULL;
        DB::table('leaveRequest')->update(['currentState' => $currentState]);
    }
}
