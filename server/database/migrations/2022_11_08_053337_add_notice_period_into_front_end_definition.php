<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoticePeriodIntoFrontEndDefinition extends Migration
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
            $basicCardKey = -1;

            $employmentTabKey = array_search('employment', array_column($editStructure, 'key'));
            if ($employmentTabKey > -1) {
                $basicCardKey = array_search('basic', array_column($editStructure[$employmentTabKey]['content'], 'key'));
            }

            if ($employmentTabKey > -1 && $basicCardKey > -1) {
                $basicCardContent = $editStructure[$employmentTabKey]['content'][$basicCardKey]['content'];

                $hasForenameField = in_array("noticePeriod", $basicCardContent);
                if (!$hasForenameField) {
                    array_push($basicCardContent, "noticePeriod");
                }

                $editStructure[$employmentTabKey]['content'][$basicCardKey]['content'] = $basicCardContent;
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
            $basicCardKey = -1;

            $employmentTabKey = array_search('employment', array_column($editStructure, 'key'));
            if ($employmentTabKey > -1) {
                $basicCardKey = array_search('basic', array_column($editStructure[$employmentTabKey]['content'], 'key'));
            }

            if ($employmentTabKey > -1 && $basicCardKey > -1) {
                $basicCardContent = $editStructure[$employmentTabKey]['content'][$basicCardKey]['content'];

                $basicCardContent = array_filter($basicCardContent, function ($str) {
                    return strpos($str, 'noticePeriod') === false;
                });

                $editStructure[$employmentTabKey]['content'][$basicCardKey]['content'] = $basicCardContent;
            }

            DB::table('frontEndDefinition')
                ->where('id', 2)
                ->update(['structure' => json_encode($editStructure)]);
        }
    }
}
