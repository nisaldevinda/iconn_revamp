<?php

use Illuminate\Database\Migrations\Migration;

class UpdateEmployeeAddFormLayoutToAddOrgStructure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $addrecord = DB::table('frontEndDefinition')->where('id', 1)->first();

        if ($addrecord) {
            $addStructure = json_decode($addrecord->structure, true);

            $jobInformationSectionKey = array_search('jobInformation', array_column($addStructure, 'key'));
            if ($jobInformationSectionKey > 0) {
                $jobInformationSectionContent = $addStructure[$jobInformationSectionKey]['content'];

                $jobInformationSectionContent = array_filter($jobInformationSectionContent, function ($item) {
                    return !in_array($item, ["jobs.department", "jobs.division"]);
                });

                if (!in_array("jobs.orgStructureEntityId", $jobInformationSectionContent)) {
                    array_push($jobInformationSectionContent, "jobs.orgStructureEntityId");
                }

                $addStructure[$jobInformationSectionKey]['content'] = array_values($jobInformationSectionContent);
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
        $addrecord = DB::table('frontEndDefinition')->where('id', 1)->first();

        if ($addrecord) {
            $addStructure = json_decode($addrecord->structure, true);

            $jobInformationSectionKey = array_search('jobInformation', array_column($addStructure, 'key'));
            if ($jobInformationSectionKey > 0) {
                $jobInformationSectionContent = $addStructure[$jobInformationSectionKey]['content'];

                $jobInformationSectionContent = array_filter($jobInformationSectionContent, function ($item) {
                    return $item != "jobs.orgStructureEntityId";
                });

                if (!in_array("jobs.department", $jobInformationSectionContent)) {
                    array_push($jobInformationSectionContent, "jobs.department");
                }

                if (!in_array("jobs.division", $jobInformationSectionContent)) {
                    array_push($jobInformationSectionContent, "jobs.division");
                }

                $addStructure[$jobInformationSectionKey]['content'] = array_values($jobInformationSectionContent);
            }

            DB::table('frontEndDefinition')
                ->where('id', 1)
                ->update(['structure' => json_encode($addStructure)]);
        }
    }
}
