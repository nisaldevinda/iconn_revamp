<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEmployeeEditFormLayout extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //Employee edit form layout definition 
        $editrecord = DB::table('frontEndDefinition')->where('id', 2)->first();

        if ($editrecord) {
            $editStructure = json_decode($editrecord->structure, true);
            $employment = array_filter($editStructure, function ($item) {
                if ($item['key'] === 'employment') {
                    return $item;
                }
            });

            if ($employment) {
                $employmentKey = array_search('employment', array_column($editStructure, 'key'));

                $employeeNumbers = array_filter($editStructure[$employmentKey]['content'], function ($item) {
                    if ($item['key'] === 'employeeNumbers') {
                        return $item;
                    }
                });

                if (!empty($employeeNumbers)) {
                    $basicSectionKey = array_search('basic', array_column($editStructure[$employmentKey]['content'], 'key'));

                    $editStructure[$employmentKey]['content'][$basicSectionKey]['content'] = [
                        "hireDate",
                        "originalHireDate",
                        "contractRenewalDate",
                        "noticePeriod",
                        "status",
                        "retirementDate"
                    ];
                }
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
        //Employee edit form layout definition 
        $editrecord = DB::table('frontEndDefinition')->where('id', 2)->first();

        if ($editrecord) {
            $editStructure = json_decode($editrecord->structure, true);
            $employment = array_filter($editStructure, function ($item) {
                if ($item['key'] === 'employment') {
                    return $item;
                }
            });

            if ($employment) {
                $employmentKey = array_search('employment', array_column($editStructure, 'key'));

                $employeeNumbers = array_filter($editStructure[$employmentKey]['content'], function ($item) {
                    if ($item['key'] === 'employeeNumbers') {
                        return $item;
                    }
                });

                if (!empty($employeeNumbers)) {
                    $basicSectionKey = array_search('basic', array_column($editStructure[$employmentKey]['content'], 'key'));

                    $editStructure[$employmentKey]['content'][$basicSectionKey]['content'] = [
                        "hireDate",
                        "originalHireDate",
                        "retirementDate",
                        "noticePeriod"
                    ];
                }
            }

            DB::table('frontEndDefinition')
                ->where('id', 2)
                ->update(['structure' => json_encode($editStructure)]);
        }
    }
}
