<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddTitleColumnToFrontEndDefinition extends Migration
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
                if ($item['key'] === 'personal') {
                    return $item;
                }
            });
            
            if ($personal) {
                $personalKey = array_search('personal', array_column($editStructure, 'key'));
                $basicInformation = array_filter($editStructure[$personalKey]['content'], function ($item) {
                    if ($item['key'] === 'basicInformation') {
                        return $item;
                    }
                });
                
                if ($basicInformation) {
                    $basicInformationKey = array_search('basicInformation', array_column($editStructure[$personalKey]['content'], 'key'));
                    $basicInformationArr = [
                        "key"=> "basicInformation",
                        "defaultLabel"=> "Basic Information",
                        "labelKey"=> "EMPLOYEE.PERSONAL.BASIC_INFORMATION",
                        "content"=> [
                                "employeeNumber",
                                "title",
                                "initials",
                                "firstName",
                                "middleName",
                                "lastName",
                                "forename",
                                "maidenName",
                                "dateOfBirth",
                                "maritalStatus",
                                "gender",
                                "bloodGroup"
                            ]
                        ];

                    $editStructure[$personalKey]['content'][$basicInformationKey] = $basicInformationArr ;
                }
            }

            DB::table('frontEndDefinition')
                ->where('id', 2)
                ->update(['structure' => json_encode($editStructure)]);

        }

        $addrecord = DB::table('frontEndDefinition')->where('id', 1)->first();

        if ($addrecord) {
            $addStructure = json_decode($addrecord->structure, true);

            $basicInformation = array_filter($addStructure, function ($item) {
                if ($item['key'] === 'basicInformation') {
                    return $item;
                }
            });

            if ($basicInformation) {
                $basicInformationKey = array_search('basicInformation', array_column($addStructure, 'key'));

                $basicInformationArr = [
                    "key" => "basicInformation",
                    "defaultLabel" => "Basic Information",
                    "labelKey" => "EMPLOYEE.BASIC_INFORMATION",
                    "content" => [
                            "employeeNumber",
                            "title",
                            "firstName",
                            "middleName",
                            "lastName",
                            "dateOfBirth",
                            "maritalStatus",
                            "gender"
                        ]
                    ];

                $addStructure[$basicInformationKey] = $basicInformationArr;


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
                if ($item['key'] === 'personal') {
                    return $item;
                }
             });
             
             if ($personal) {
                $personalKey = array_search('personal', array_column($structure, 'key'));
                $basicInformation = array_filter($structure[$personalKey]['content'], function ($item) {
                    if ($item['key'] === 'basicInformation') {
                        return $item;
                    }
                });
                
                if ($basicInformation) {
                $basicInformationKey = array_search('basicInformation', array_column($structure[$personalKey]['content'], 'key'));
                

                if (in_array('title', $structure[$personalKey]['content'][$basicInformationKey]['content'])) {
                    $structure[$personalKey]['content'][$basicInformationKey]['content'] = array_diff($structure[$personalKey]['content'][$basicInformationKey]['content'], array('title'));;
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
                if ($item['key'] === 'basicInformation') {
                    return $item;
                }
            });
 
            if ($basicInformation) {
                $basicInformationKey = array_search('basicInformation', array_column($structure, 'key'));

                if (in_array('title', $structure[$basicInformationKey]['content'])) {
                    $structure[$basicInformationKey]['content'] = array_diff($structure[$basicInformationKey]['content'], array('title'));;
                }

            }
 
            DB::table('frontEndDefinition')
                 ->where('id', 1)
                 ->update(['structure' => json_encode($structure)]);
        }
    }
}
