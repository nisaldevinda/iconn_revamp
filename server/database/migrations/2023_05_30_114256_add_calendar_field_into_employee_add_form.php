<?php

use Illuminate\Database\Migrations\Migration;

class AddCalendarFieldIntoEmployeeAddForm extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // get add definition
        $addRecord = DB::table('frontEndDefinition')->where('id', 1)->first();

        if ($addRecord) {
            $addStructure = json_decode($addRecord->structure, true);

            $jobSectionIndex = array_search('jobInformation', array_column($addStructure, 'key'));
            if ($jobSectionIndex > -1) {
                $jobSection = $addStructure[$jobSectionIndex]['content'];

                $reportsToEmployeeIndex = array_search('jobs.location', $jobSection);
                if ($reportsToEmployeeIndex > -1) {
                    $reportsToEmployeeIndex++;
                    array_splice($jobSection, $reportsToEmployeeIndex, 0, 'jobs.calendar');
                }

                $addStructure[$jobSectionIndex]['content'] = $jobSection;
            }

            DB::table('frontEndDefinition')
                ->where('id', 1)
                ->update(['structure' => json_encode($addStructure)]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // get add definition
        $addRecord = DB::table('frontEndDefinition')->where('id', 1)->first();

        if ($addRecord) {
            $addStructure = json_decode($addRecord->structure, true);

            $jobSectionIndex = array_search('jobInformation', array_column($addStructure, 'key'));
            if ($jobSectionIndex > -1) {
                $jobSection = $addStructure[$jobSectionIndex]['content'];

                $reportsToEmployeeIndex = array_search('jobs.calendar', $jobSection);
                if ($reportsToEmployeeIndex > -1) {
                    unset($jobSection[$reportsToEmployeeIndex]);
                }

                $addStructure[$jobSectionIndex]['content'] = $jobSection;
            }

            DB::table('frontEndDefinition')
                ->where('id', 1)
                ->update(['structure' => json_encode($addStructure)]);
        }
    }
}
