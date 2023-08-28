<?php

use Illuminate\Database\Migrations\Migration;

class UpdateEmployeeEditFormLayoutForAddSalarySection extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $editrecord = DB::table('frontEndDefinition')->where('id', 2)->first();

        if ($editrecord) {
            $editStructure = json_decode($editrecord->structure, true);

            $compensationTabKey = array_search('compensation', array_column($editStructure, 'key'));
            if ($compensationTabKey > 0) {
                $salarySectionKey = array_search('salary', array_column($editStructure[$compensationTabKey]['content'], 'key'));
                $editStructure[$compensationTabKey]['content'][$salarySectionKey]['content'] = [
                    "employeeSalarySection"
                ];
            }

            DB::table('frontEndDefinition')
                ->where('id', 2)
                ->update(['structure' => json_encode($editStructure)]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $editrecord = DB::table('frontEndDefinition')->where('id', 2)->first();

        if ($editrecord) {
            $editStructure = json_decode($editrecord->structure, true);

            $compensationTabKey = array_search('compensation', array_column($editStructure, 'key'));
            if ($compensationTabKey > 0) {
                $salarySectionKey = array_search('salary', array_column($editStructure[$compensationTabKey]['content'], 'key'));
                $editStructure[$compensationTabKey]['content'][$salarySectionKey]['content'] = [
                    "salaries"
                ];
            }

            DB::table('frontEndDefinition')
                ->where('id', 2)
                ->update(['structure' => json_encode($editStructure)]);
        }
    }
}
