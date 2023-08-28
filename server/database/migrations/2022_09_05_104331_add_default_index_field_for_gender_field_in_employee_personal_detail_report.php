<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDefaultIndexFieldForGenderFieldInEmployeePersonalDetailReport extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $employeePersonalDetailsReport = DB::table('reportData')->where('id', '=', 2)->first();

        if (!empty($employeePersonalDetailsReport)) {
            $selectedTables = json_decode($employeePersonalDetailsReport->selectedTables);


            if (!empty($selectedTables)) {
                foreach ($selectedTables as $key => $selectedTable) {
                    $selectedTable = (array) $selectedTable;
                    if ($selectedTable['key'] == 'gender') {
                        $selectedTables[$key]->dataIndex = 'personalgendername';
                    }
                }
            }

            $selectedTables = json_encode($selectedTables);
            DB::table('reportData')->where('id', 2)->update(['selectedTables' => $selectedTables]);

        }


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $employeePersonalDetailsReport = DB::table('reportData')->where('id', '=', 2)->first();

        if (!empty($employeePersonalDetailsReport)) {
            $selectedTables = json_decode($employeePersonalDetailsReport->selectedTables);


            if (!empty($selectedTables)) {
                foreach ($selectedTables as $key => $selectedTable) {
                    $selectedTable = (array) $selectedTable;
                    if ($selectedTable['key'] == 'gender') {
                        unset($selectedTables[$key]->dataIndex);
                    }
                }
            }

            $selectedTables = json_encode($selectedTables);
            DB::table('reportData')->where('id', 2)->update(['selectedTables' => $selectedTables]);

        }
    }
}
