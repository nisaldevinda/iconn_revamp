<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFunctionalReportToFieldIntoEmployeeAddForm extends Migration
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

                $reportsToEmployeeIndex = array_search('jobs.reportsToEmployee', $jobSection);
                if ($reportsToEmployeeIndex > -1) {
                    $reportsToEmployeeIndex++;
                    array_splice($jobSection, $reportsToEmployeeIndex, 0, 'jobs.functionalReportsToEmployee');
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

                $reportsToEmployeeIndex = array_search('jobs.functionalReportsToEmployee', $jobSection);
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
