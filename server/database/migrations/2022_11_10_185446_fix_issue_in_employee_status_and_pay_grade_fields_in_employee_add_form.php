<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixIssueInEmployeeStatusAndPayGradeFieldsInEmployeeAddForm extends Migration
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

            $jobSectionIndex = array_search('job', array_column($addStructure, 'key'));
            if ($jobSectionIndex > -1) {
                $jobSection = $addStructure[$jobSectionIndex]['content'];

                $payGradeIndex = array_search('payGrade', $jobSection);
                if ($payGradeIndex > -1) {
                    $jobSection[$payGradeIndex] = 'jobs.payGrade';
                }

                $addStructure[$jobSectionIndex]['content'] = $jobSection;
            }

            $employmentStatusSectionIndex = array_search('employmentStatus', array_column($addStructure, 'key'));
            if ($employmentStatusSectionIndex > -1) {
                $employmentStatusSection = $addStructure[$employmentStatusSectionIndex]['content'];

                $employmentStatusIndex = array_search('employments.employmentStatus', $employmentStatusSection);
                if ($employmentStatusIndex > -1) {
                    $employmentStatusSection[$employmentStatusIndex] = 'jobs.employmentStatus';
                }

                $addStructure[$employmentStatusSectionIndex]['content'] = $employmentStatusSection;
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

            $jobSectionIndex = array_search('job', array_column($addStructure, 'key'));
            if ($jobSectionIndex > -1) {
                $jobSection = $addStructure[$jobSectionIndex]['content'];

                $payGradeIndex = array_search('jobs.payGrade', $jobSection);
                if ($payGradeIndex > -1) {
                    $jobSection[$payGradeIndex] = 'payGrade';
                }

                $addStructure[$jobSectionIndex]['content'] = $jobSection;
            }

            $employmentStatusSectionIndex = array_search('employmentStatus', array_column($addStructure, 'key'));
            if ($employmentStatusSectionIndex > -1) {
                $employmentStatusSection = $addStructure[$employmentStatusSectionIndex]['content'];

                $employmentStatusIndex = array_search('jobs.employmentStatus', $employmentStatusSection);
                if ($employmentStatusIndex > -1) {
                    $employmentStatusSection[$employmentStatusIndex] = 'employments.employmentStatus';
                }

                $addStructure[$employmentStatusSectionIndex]['content'] = $employmentStatusSection;
            }

            DB::table('frontEndDefinition')
                ->where('id', 1)
                ->update(['structure' => json_encode($addStructure)]);
        }
    }
}
