<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEmployeeProfileEditLayoutWithEpfNumber extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // add epfNumber into employee add form
        $addrecord = DB::table('frontEndDefinition')->where('id', 1)->first();

        if ($addrecord) {
            $addStructure = json_decode($addrecord->structure, true);

            $employeeNumbersCardIndex = array_search('employeeNumbers', array_column($addStructure, 'key'));
            if (
                $employeeNumbersCardIndex > -1
                && !in_array('epfNumber', $addStructure[$employeeNumbersCardIndex]['content'])
            ) {
                $addStructure[$employeeNumbersCardIndex]['content'][] = 'epfNumber';
                DB::table('frontEndDefinition')
                    ->where('id', 1)
                    ->update(['structure' => json_encode($addStructure)]);
            }
        }

        // add epfNumber into employee edit form 
        $editrecord = DB::table('frontEndDefinition')->where('id', 2)->first();

        if ($editrecord) {
            $editStructure = json_decode($editrecord->structure, true);

            $employmentTabIndex = array_search('employment', array_column($editStructure, 'key'));
            if ($employmentTabIndex > -1) {
                $employmentTab = $editStructure[$employmentTabIndex];

                $employeeNumbersCardIndex = array_search('employeeNumbers', array_column($employmentTab['content'], 'key'));
                if (
                    $employeeNumbersCardIndex > -1
                    && !in_array('epfNumber', $editStructure[$employmentTabIndex]['content'][$employeeNumbersCardIndex]['content'])
                ) {
                    $editStructure[$employmentTabIndex]['content'][$employeeNumbersCardIndex]['content'][] = 'epfNumber';
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
        // remove epfNumber from employee add form
        $addrecord = DB::table('frontEndDefinition')->where('id', 1)->first();

        if ($addrecord) {
            $addStructure = json_decode($addrecord->structure, true);

            $employeeNumbersCardIndex = array_search('employeeNumbers', array_column($addStructure, 'key'));
            if (
                $employeeNumbersCardIndex > -1
                && in_array('epfNumber', $addStructure[$employeeNumbersCardIndex]['content'])
            ) {
                $addStructure[$employeeNumbersCardIndex]['content'] = array_filter(
                    $addStructure[$employeeNumbersCardIndex]['content'],
                    function ($fieldname) {
                        return $fieldname != 'epfNumber';
                    }
                );

                DB::table('frontEndDefinition')
                    ->where('id', 1)
                    ->update(['structure' => json_encode($addStructure)]);
            }
        }

        // remove epfNumber from employee edit form 
        $editrecord = DB::table('frontEndDefinition')->where('id', 2)->first();

        if ($editrecord) {
            $editStructure = json_decode($editrecord->structure, true);

            $employmentTabIndex = array_search('employment', array_column($editStructure, 'key'));
            if ($employmentTabIndex > -1) {
                $employmentTab = $editStructure[$employmentTabIndex];

                $employeeNumbersCardIndex = array_search('employeeNumbers', array_column($employmentTab['content'], 'key'));
                if (
                    $employeeNumbersCardIndex > -1
                    && in_array('epfNumber', $editStructure[$employmentTabIndex]['content'][$employeeNumbersCardIndex]['content'])
                ) {
                    $editStructure[$employmentTabIndex]['content'][$employeeNumbersCardIndex]['content'] = array_filter(
                        $editStructure[$employmentTabIndex]['content'][$employeeNumbersCardIndex]['content'],
                        function ($fieldname) {
                            return $fieldname != 'epfNumber';
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
