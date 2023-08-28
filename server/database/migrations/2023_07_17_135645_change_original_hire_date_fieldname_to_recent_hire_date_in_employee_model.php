<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeOriginalHireDateFieldnameToRecentHireDateInEmployeeModel extends Migration
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

            if (empty($fields['originalHireDate'])) return;

            $recentHireDate = $fields['originalHireDate'];
            $recentHireDate['name'] = 'recentHireDate';
            $fields['recentHireDate'] = $recentHireDate;

            unset($fields['originalHireDate']);

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

            if (empty($fields['recentHireDate'])) return;

            $originalHireDate = $fields['recentHireDate'];
            $originalHireDate['name'] = 'originalHireDate';
            $fields['originalHireDate'] = $originalHireDate;

            unset($fields['recentHireDate']);

            $employeeModel['fields'] = $fields;

            DB::table('dynamicModel')->where('modelName', 'employee')->update(["dynamicModel" => $employeeModel]);
        }
    }
}
