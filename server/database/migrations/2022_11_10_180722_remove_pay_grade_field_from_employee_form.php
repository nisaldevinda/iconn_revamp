<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemovePayGradeFieldFromEmployeeForm extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // get edit definition
        $editrecord = DB::table('frontEndDefinition')->where('id', 2)->first();

        if ($editrecord) {
            $editStructure = json_decode($editrecord->structure, true);

            $compensationTabKey = -1;
            $basicCardKey = -1;

            $compensationTabKey = array_search('compensation', array_column($editStructure, 'key'));
            if ($compensationTabKey > -1) {
                $basicCardKey = array_search('basic', array_column($editStructure[$compensationTabKey]['content'], 'key'));
            }

            if ($compensationTabKey > -1 && $basicCardKey > -1) {
                $basicCardContent = $editStructure[$compensationTabKey]['content'][$basicCardKey]['content'];

                $basicCardContent = array_values(array_diff($basicCardContent, ['payGrade']));

                $editStructure[$compensationTabKey]['content'][$basicCardKey]['content'] = $basicCardContent;
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
        // get edit definition
        $editrecord = DB::table('frontEndDefinition')->where('id', 2)->first();

        if ($editrecord) {
            $editStructure = json_decode($editrecord->structure, true);

            $compensationTabKey = -1;
            $basicCardKey = -1;

            $compensationTabKey = array_search('compensation', array_column($editStructure, 'key'));
            if ($compensationTabKey > -1) {
                $basicCardKey = array_search('basic', array_column($editStructure[$compensationTabKey]['content'], 'key'));
            }

            if ($compensationTabKey > -1 && $basicCardKey > -1) {
                $basicCardContent = $editStructure[$compensationTabKey]['content'][$basicCardKey]['content'];

                $hasForenameField = in_array("payGrade", $basicCardContent);
                if (!$hasForenameField) {
                    array_push($basicCardContent, "payGrade");
                }

                $editStructure[$compensationTabKey]['content'][$basicCardKey]['content'] = $basicCardContent;
            }

            DB::table('frontEndDefinition')
                ->where('id', 2)
                ->update(['structure' => json_encode($editStructure)]);
        }
    }
}
