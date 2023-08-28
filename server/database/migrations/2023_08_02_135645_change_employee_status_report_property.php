<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ChangeEmployeeStatusReportProperty extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $employeeModel = DB::table('dynamicModel')->where('modelName', 'employee')->first();

        if (!empty($employeeModel)) {
            $employeeModel = json_decode($employeeModel->dynamicModel, true);

            if (empty($employeeModel['fields'])) return;            
            $fields = $employeeModel['fields'];

            if (empty($fields['status'])) return;

            $fields['status']['reportField'] = 'isActive';

            $employeeModel['fields'] = $fields;

            DB::table('dynamicModel')->where('modelName', 'employee')->update(["dynamicModel" => $employeeModel]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $employeeModel = DB::table('dynamicModel')->where('modelName', 'employee')->first();

        if (!empty($employeeModel)) {
            $employeeModel = json_decode($employeeModel->dynamicModel, true);

            if (empty($employeeModel['fields'])) return;            
            $fields = $employeeModel['fields'];

            if (empty($fields['status'])) return;

            if (empty($fields['status']['reportField'])) return;

            $status = $fields['status'];
            unset($status['reportField']);
            $fields['status'] = $status;

            DB::table('dynamicModel')->where('modelName', 'employee')->update(["dynamicModel" => $employeeModel]);
        }
    }
}
