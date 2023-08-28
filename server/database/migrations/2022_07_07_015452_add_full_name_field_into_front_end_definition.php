<?php

use AWS\CRT\Log;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFullNameFieldIntoFrontEndDefinition extends Migration
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

            $personalTabKey = -1;
            $basicInformationCardKey = -1;

            $personalTabKey = array_search('personal', array_column($editStructure, 'key'));
            if ($personalTabKey > -1) {
                $basicInformationCardKey = array_search('basicInformation', array_column($editStructure[$personalTabKey]['content'], 'key'));
            }

            if ($personalTabKey > -1 && $basicInformationCardKey > -1) {
                $basicInformationCardContent = $editStructure[$personalTabKey]['content'][$basicInformationCardKey]['content'];

                $hasForenameField = in_array("forename", $basicInformationCardContent);
                if ($hasForenameField) {
                    $basicInformationCardContent = str_replace('forename', 'fullName', $basicInformationCardContent);
                } else {
                    $basicInformationCardContent = array_merge(
                        array_slice($basicInformationCardContent, 0, 7),
                        array('fullName'),
                        array_slice($basicInformationCardContent, 6)
                    );
                }

                $editStructure[$personalTabKey]['content'][$basicInformationCardKey]['content'] = $basicInformationCardContent;
            }

            DB::table('frontEndDefinition')
                ->where('id', 2)
                ->update(['structure' => json_encode($editStructure)]);
        }

        // get add definition
        $addrecord = DB::table('frontEndDefinition')->where('id', 1)->first();

        if ($addrecord) {
            $addStructure = json_decode($addrecord->structure, true);

            $basicInformationCardKey = -1;
            $basicInformationCardKey = array_search('basicInformation', array_column($addStructure, 'key'));

            if ($basicInformationCardKey > -1) {
                $basicInformationCardContent = $addStructure[$basicInformationCardKey]['content'];

                $hasForenameField = in_array("forename", $basicInformationCardContent);
                if ($hasForenameField) {
                    $basicInformationCardContent = str_replace('forename', 'fullName', $basicInformationCardContent);
                } else {
                    $basicInformationCardContent = array_merge(
                        array_slice($basicInformationCardContent, 0, 6),
                        array('fullName'),
                        array_slice($basicInformationCardContent, 6)
                    );
                }

                $addStructure[$basicInformationCardKey]['content'] = $basicInformationCardContent;
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
            $editStructure = json_decode($editrecord->structure, true);

            $personalTabKey = -1;
            $basicInformationCardKey = -1;

            $personalTabKey = array_search('personal', array_column($editStructure, 'key'));
            if ($personalTabKey > -1) {
                $basicInformationCardKey = array_search('basicInformation', array_column($editStructure[$personalTabKey]['content'], 'key'));
            }

            if ($personalTabKey > -1 && $basicInformationCardKey > -1) {
                $basicInformationCardContent = $editStructure[$personalTabKey]['content'][$basicInformationCardKey]['content'];

                $basicInformationCardContent = array_filter($basicInformationCardContent, function($str) {
                    return strpos($str, 'fullName') === false;
                });

                $editStructure[$personalTabKey]['content'][$basicInformationCardKey]['content'] = $basicInformationCardContent;
            }

            DB::table('frontEndDefinition')
                ->where('id', 2)
                ->update(['structure' => json_encode($editStructure)]);
        }

        // get add definition
        $addrecord = DB::table('frontEndDefinition')->where('id', 1)->first();

        if ($addrecord) {
            $addStructure = json_decode($addrecord->structure, true);

            $basicInformationCardKey = array_search('basicInformation', array_column($addStructure, 'key'));

            if ($basicInformationCardKey > -1) {
                $basicInformationCardContent = $addStructure[$basicInformationCardKey]['content'];

                $basicInformationCardContent = array_filter($basicInformationCardContent, function($str) {
                    return strpos($str, 'fullName') === false;
                });

                $addStructure[$personalTabKey]['content'][$basicInformationCardKey]['content'] = $basicInformationCardContent;
            }

            DB::table('frontEndDefinition')
                ->where('id', 1)
                ->update(['structure' => json_encode($addStructure)]);
        }
    }
}
