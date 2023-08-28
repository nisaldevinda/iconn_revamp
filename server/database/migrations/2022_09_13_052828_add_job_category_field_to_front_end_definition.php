<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddJobCategoryFieldToFrontEndDefinition extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // get add definition
        $addrecord = DB::table('frontEndDefinition')->where('id', 1)->first();

        if ($addrecord) {
            $addStructure = json_decode($addrecord->structure, true);

            $jobInformation = array_filter($addStructure, function ($item) {
                if ($item['key'] === 'jobInformation') {
                    return $item;
                }
            });

            if ($jobInformation) {
                $jobInformationKey = array_search('jobInformation', array_column($addStructure, 'key'));

                $jobInformationArr = [
                    "key" => "jobInformation",
                    "defaultLabel" => "Job Information",
                    "labelKey" => "EMPLOYEE.JOB_INFORMATION",
                    "content" => [
                        "jobs.jobTitle",
                        "jobs.jobCategory",
                        "jobs.reportsToEmployee",
                        "jobs.department",
                        "jobs.division",
                        "jobs.location"
                    ]
                ];

                $addStructure[$jobInformationKey] = $jobInformationArr;
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
         $addrecord = DB::table('frontEndDefinition')->where('id', 1)->first();
 
         if ($addrecord) {
            $structure = json_decode($addrecord->structure, true);

            $jobInformation = array_filter($structure, function ($item) {
                if ($item['key'] === 'jobInformation') {
                    return $item;
                }
            });
 
            if ($jobInformation) {
                $jobInformationKey = array_search('jobInformation', array_column($structure, 'key'));

                if (in_array('title', $structure[$jobInformationKey]['content'])) {
                    $structure[$jobInformationKey]['content'] = array_diff($structure[$jobInformationKey]['content'], array('jobs.jobCategory'));;
                }

            }
 
            DB::table('frontEndDefinition')
                 ->where('id', 1)
                 ->update(['structure' => json_encode($structure)]);
        }
    }
}
