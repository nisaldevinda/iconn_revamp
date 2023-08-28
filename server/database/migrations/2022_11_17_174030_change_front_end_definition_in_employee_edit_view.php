<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeFrontEndDefinitionInEmployeeEditView extends Migration
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

            $employmentTabKey = -1;
            $jobSectionKey = -1;
            $employmentSectionKey = -1;

            $employmentTabKey = array_search('employment', array_column($editStructure, 'key'));
            if ($employmentTabKey > -1) {
                $jobSectionKey = array_search('job', array_column($editStructure[$employmentTabKey]['content'], 'key'));
                $employmentSectionKey = array_search('employment', array_column($editStructure[$employmentTabKey]['content'], 'key'));
            }

            if ($jobSectionKey > -1) {
                $editStructure[$employmentTabKey]['content'][$jobSectionKey]['content'] = ['employeeJourney'];
            }

            if ($employmentSectionKey > -1) {
                $employmentTabContent = $editStructure[$employmentTabKey]['content'];
                unset($employmentTabContent[$employmentSectionKey]);
                $employmentTabContent = array_values($employmentTabContent);
                $editStructure[$employmentTabKey]['content'] = $employmentTabContent;
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

            $employmentTabKey = -1;
            $employmentSectionKey = -1;
            $jobSectionKey = -1;

            $employmentTabKey = array_search('employment', array_column($editStructure, 'key'));
            if ($employmentTabKey > -1) {
                $employmentSectionKey = array_search('employment', array_column($editStructure[$employmentTabKey]['content'], 'key'));
                $jobSectionKey = array_search('job', array_column($editStructure[$employmentTabKey]['content'], 'key'));
            }

            if ($employmentSectionKey > -1) {
                $content = [
                    "key" => "employment",
                    "defaultLabel" => "Employment",
                    "labelKey" => "EMPLOYEE.EMPLOYMENT.EMPLOYMENT",
                    "content" => [
                        "employments"
                    ]
                ];

                $employmentTabContent = $editStructure[$employmentTabKey]['content'];
                $editStructure[$employmentTabKey]['content'] = [
                    $employmentTabContent[0],
                    $content,
                    $employmentTabContent[1],
                ];
            }

            if ($jobSectionKey > -1) {
                $editStructure[$employmentTabKey]['content'][$jobSectionKey]['content'] = ['jobs'];
            }

            DB::table('frontEndDefinition')
                ->where('id', 2)
                ->update(['structure' => json_encode($editStructure)]);
        }
    }
}
