<?php

use Illuminate\Database\Migrations\Migration;

class UpdateEmployeeProfileEditLayoutWithetfNumber extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // add etfNumber into employee add form
        $addrecord = DB::table('frontEndDefinition')->where('id', 1)->first();

        if ($addrecord) {
            $addStructure = json_decode($addrecord->structure, true);

            $employeeNumbersCardIndex = array_search('employeeNumbers', array_column($addStructure, 'key'));
            if (
                $employeeNumbersCardIndex > -1
                && !in_array('etfNumber', $addStructure[$employeeNumbersCardIndex]['content'])
            ) {
                $addStructure[$employeeNumbersCardIndex]['content'][] = 'etfNumber';
                DB::table('frontEndDefinition')
                    ->where('id', 1)
                    ->update(['structure' => json_encode($addStructure)]);
            }
        }

        // add etfNumber into employee edit form
        $editrecord = DB::table('frontEndDefinition')->where('id', 2)->first();

        if ($editrecord) {
            $editStructure = json_decode($editrecord->structure, true);

            $employmentTabIndex = array_search('employment', array_column($editStructure, 'key'));
            if ($employmentTabIndex > -1) {
                $employmentTab = $editStructure[$employmentTabIndex];

                $employeeNumbersCardIndex = array_search('employeeNumbers', array_column($employmentTab['content'], 'key'));
                if (
                    $employeeNumbersCardIndex > -1
                    && !in_array('etfNumber', $editStructure[$employmentTabIndex]['content'][$employeeNumbersCardIndex]['content'])
                ) {
                    $editStructure[$employmentTabIndex]['content'][$employeeNumbersCardIndex]['content'][] = 'etfNumber';
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
        // remove etfNumber from employee add form
        $addrecord = DB::table('frontEndDefinition')->where('id', 1)->first();

        if ($addrecord) {
            $addStructure = json_decode($addrecord->structure, true);

            $employeeNumbersCardIndex = array_search('employeeNumbers', array_column($addStructure, 'key'));
            if (
                $employeeNumbersCardIndex > -1
                && in_array('etfNumber', $addStructure[$employeeNumbersCardIndex]['content'])
            ) {
                $addStructure[$employeeNumbersCardIndex]['content'] = array_filter(
                    $addStructure[$employeeNumbersCardIndex]['content'],
                    function ($fieldname) {
                        return $fieldname != 'etfNumber';
                    }
                );

                DB::table('frontEndDefinition')
                    ->where('id', 1)
                    ->update(['structure' => json_encode($addStructure)]);
            }
        }

        // remove etfNumber from employee edit form
        $editrecord = DB::table('frontEndDefinition')->where('id', 2)->first();

        if ($editrecord) {
            $editStructure = json_decode($editrecord->structure, true);

            $employmentTabIndex = array_search('employment', array_column($editStructure, 'key'));
            if ($employmentTabIndex > -1) {
                $employmentTab = $editStructure[$employmentTabIndex];

                $employeeNumbersCardIndex = array_search('employeeNumbers', array_column($employmentTab['content'], 'key'));
                if (
                    $employeeNumbersCardIndex > -1
                    && in_array('etfNumber', $editStructure[$employmentTabIndex]['content'][$employeeNumbersCardIndex]['content'])
                ) {
                    $editStructure[$employmentTabIndex]['content'][$employeeNumbersCardIndex]['content'] = array_filter(
                        $editStructure[$employmentTabIndex]['content'][$employeeNumbersCardIndex]['content'],
                        function ($fieldname) {
                            return $fieldname != 'etfNumber';
                        }
                    );

                    DB::table('frontEndDefinition')
                        ->where('id', 2)
                        ->update(['structure' => json_encode($editStructure)]);
                }
            }
        }
    }
}
