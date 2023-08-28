<?php

use Illuminate\Database\Migrations\Migration;

class UpdateEmployeeProfileLayoutsWithRecentHireDateField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // add etfNumber into employee edit form
        $editrecord = DB::table('frontEndDefinition')->where('id', 2)->first();

        if ($editrecord) {
            $editStructure = json_decode($editrecord->structure, true);

            $employmentTabIndex = array_search('employment', array_column($editStructure, 'key'));
            if ($employmentTabIndex > -1) {
                $employmentTab = $editStructure[$employmentTabIndex];

                $basicCardIndex = array_search('basic', array_column($employmentTab['content'], 'key'));
                if (
                    $basicCardIndex > -1
                    && in_array('originalHireDate', $editStructure[$employmentTabIndex]['content'][$basicCardIndex]['content'])
                ) {
                    $editStructure[$employmentTabIndex]['content'][$basicCardIndex]['content'] = array_map(function ($fieldName) {
                        return $fieldName == 'originalHireDate' ? 'recentHireDate' : $fieldName;
                    }, $editStructure[$employmentTabIndex]['content'][$basicCardIndex]['content']);

                    DB::table('frontEndDefinition')
                        ->where('id', 2)
                        ->update(['structure' => json_encode($editStructure)]);
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
        // remove etfNumber from employee edit form
        $editrecord = DB::table('frontEndDefinition')->where('id', 2)->first();

        if ($editrecord) {
            $editStructure = json_decode($editrecord->structure, true);

            $employmentTabIndex = array_search('employment', array_column($editStructure, 'key'));
            if ($employmentTabIndex > -1) {
                $employmentTab = $editStructure[$employmentTabIndex];

                $basicCardIndex = array_search('basic', array_column($employmentTab['content'], 'key'));
                if (
                    $basicCardIndex > -1
                    && in_array('recentHireDate', $editStructure[$employmentTabIndex]['content'][$basicCardIndex]['content'])
                ) {
                    $editStructure[$employmentTabIndex]['content'][$basicCardIndex]['content'] = array_map(function ($fieldName) {
                        return $fieldName == 'recentHireDate' ? 'originalHireDate' : $fieldName;
                    }, $editStructure[$employmentTabIndex]['content'][$basicCardIndex]['content']);

                    DB::table('frontEndDefinition')
                        ->where('id', 2)
                        ->update(['structure' => json_encode($editStructure)]);
                }
            }
        }
    }
}
