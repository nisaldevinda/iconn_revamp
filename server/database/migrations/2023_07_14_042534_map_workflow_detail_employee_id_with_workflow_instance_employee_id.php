<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MapWorkflowDetailEmployeeIdWithWorkflowInstanceEmployeeId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $workflowDetails = DB::table('workflowDetail')->select(['id', 'employeeId', 'instanceId'])->where('isDelete', false)->get();

        if (!is_null($workflowDetails)) {

            foreach ($workflowDetails as $key => $value) {
                $value = (array) $value;
                if (!empty($value['instanceId']) && !empty($value['employeeId'])) {
                   $updateEmployeeId =  DB::table('workflowInstance')->where('id', $value['instanceId'])
                    ->update(['workflowEmployeeId' => $value['employeeId']]);
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
        $updateEmployeeId =  DB::table('workflowInstance')->update(['workflowEmployeeId' => null]);
    }
}
