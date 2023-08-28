<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateFrontEndDefinitionForIsOtAllowedField extends Migration
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
            $personal = array_filter($editStructure, function ($item) {
                if ($item['key'] === 'compensation') {
                    return $item;
                }
            });

            if ($personal) {
                $compensationKey = array_search('compensation', array_column($editStructure, 'key'));
                $basicInformation = array_filter($editStructure[$compensationKey]['content'], function ($item) {
                    if ($item['key'] === 'basic') {
                        return $item;
                    }
                });

                if ($basicInformation) {
                    $basicKey = array_search('basic', array_column($editStructure[$compensationKey]['content'], 'key'));
                    $basicInformationArr = [
                        "key"=> "basic",
                        "defaultLabel"=> "Basic",
                        "labelKey"=> "EMPLOYEE.BASIC",
                        "content"=> [
                            "payGrade",
                            "isOTAllowed"
                        ]
                    ];

                    $editStructure[$compensationKey]['content'][$basicKey] = $basicInformationArr;
                }
            }


            DB::table('frontEndDefinition')
                ->where('id', 2)
                ->update(['structure' => json_encode($editStructure)]);
        }

        $addrecord = DB::table('frontEndDefinition')->where('id', 1)->first();

        if ($addrecord) {
            $addStructure = json_decode($addrecord->structure, true);

            $job = array_filter($addStructure, function ($item) {
                if ($item['key'] === 'job') {
                    return $item;
                }
            });

            if ($job) {
                $jobKey = array_search('job', array_column($addStructure, 'key'));

                $jobArr = [
                    "key" => "job",
                    "defaultLabel" => "Job",
                    "labelKey" => "EMPLOYEE.JOB",
                    "content" => [
                        "hireDate",
                        "payGrade",
                        "isOTAllowed"
                    ]
                ];

                $addStructure[$jobKey] = $jobArr;
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
        // get edit definition
        $editrecord = DB::table('frontEndDefinition')->where('id', 2)->first();

        if ($editrecord) {
            $structure = json_decode($editrecord->structure, true);
            $personal = array_filter($structure, function ($item) {
                if ($item['key'] === 'compensation') {
                    return $item;
                }
            });

            if ($personal) {
                $compensationKey = array_search('compensation', array_column($structure, 'key'));
                $basicInformation = array_filter($structure[$compensationKey]['content'], function ($item) {
                    if ($item['key'] === 'basic') {
                        return $item;
                    }
                });

                if ($basicInformation) {
                    $basicKey = array_search('basic', array_column($structure[$compensationKey]['content'], 'key'));


                    if (in_array('isOTAllowed', $structure[$compensationKey]['content'][$basicKey]['content'])) {
                        $structure[$compensationKey]['content'][$basicKey]['content'] = array_diff($structure[$compensationKey]['content'][$basicKey]['content'], array('isOTAllowed'));;
                    }
                }
            }

            DB::table('frontEndDefinition')
                ->where('id', 2)
                ->update(['structure' => json_encode($structure)]);
        }

        $addrecord = DB::table('frontEndDefinition')->where('id', 1)->first();

        if ($addrecord) {
            $structure = json_decode($addrecord->structure, true);

            $basicInformation = array_filter($structure, function ($item) {
                if ($item['key'] === 'job') {
                    return $item;
                }
            });

            if ($basicInformation) {
                $basicInformationKey = array_search('job', array_column($structure, 'key'));

                if (in_array('isOTAllowed', $structure[$basicInformationKey]['content'])) {
                    $structure[$basicInformationKey]['content'] = array_diff($structure[$basicInformationKey]['content'], array('isOTAllowed'));;
                }
            }

            DB::table('frontEndDefinition')
                ->where('id', 1)
                ->update(['structure' => json_encode($structure)]);
        }
    }
}
