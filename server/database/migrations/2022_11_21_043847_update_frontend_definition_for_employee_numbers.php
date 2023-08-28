<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFrontendDefinitionForEmployeeNumbers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //add employee frontEnd defintion
        $addrecord = DB::table('frontEndDefinition')->where('id', 1)->first();

        if ($addrecord) {
            $addStructure = json_decode($addrecord->structure, true);
   
            $employeeNumbers = array_filter($addStructure, function ($item) {
                if ($item['key'] === 'employeeNumbers') {
                    return $item;
                }
            });

            if (!empty($employeeNumbers))  {
                  $index = 0;
                  $content =  [
                    "key"=> "employeeNumbers",
                    "defaultLabel"=> "Employee Identification Numbers",
                    "labelKey"=> "EMPLOYEE.PERSONAL.IDENTIFICATION_NUMBER",
                    "content"=> [
                       "employeeNumber",
                       "attendanceId"
                    ]
                  ];
                  $addStructure[$index] = $content;
            
            }
          
            DB::table('frontEndDefinition')
               ->where('id', 1)
               ->update(['structure' => json_encode($addStructure)]);
        }
         
        //edit Employee frontEnd definition 
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
                
                $employeeNumberSectionKey = array_search('employeeNumbers', array_column($editStructure[$personalKey]['content'],'key'));
                $employeeNumbers= array_filter($editStructure[$personalKey]['content'], function ($item) {
                    if ($item['key'] === 'employeeNumbers') {
                        return $item;
                         }
                    });
  
                if (!empty($employeeNumbers))  {
                    unset($editStructure[$personalKey]['content'][ $employeeNumberSectionKey]);
                     
                }
                $editStructure[$personalKey]['content'] = array_values($editStructure[$personalKey]['content']);       
            }
            $employment = array_filter($editStructure, function ($item) {
                if ($item['key'] === 'employment') {
                    return $item;
                  }
                });
            
            if ($employment) {
                $employmentlKey = array_search('employment', array_column($editStructure, 'key'));
                $employeeNumbers= array_filter($editStructure[$employmentlKey]['content'], function ($item) {
                    if ($item['key'] === 'employeeNumbers') {
                        return $item;
                        }
                    });
               
                if (empty($employeeNumbers))  {
                    $index = 1;
                    $content =  [
                        "key"=> "employeeNumbers",
                        "defaultLabel"=> "Employee Identification Numbers",
                        "labelKey"=> "EMPLOYEE.EMPLOYMENT.IDENTIFICATION_NUMBER",
                        "content"=> [
                          "employeeNumber",
                          "attendanceId"
                        ]
                    ];
                         
                    $editStructure[$employmentlKey]['content'] = array_merge(array_slice($editStructure[$employmentlKey]['content'], 0, $index), array($content), array_slice($editStructure[$employmentlKey]['content'], $index));
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
        //add employee frontEnd defintion
        $addrecord = DB::table('frontEndDefinition')->where('id', 1)->first();

        if ($addrecord) {
            $addStructure = json_decode($addrecord->structure, true);
      
            $employeeNumbers = array_filter($addStructure, function ($item) {
                if ($item['key'] === 'employeeNumbers') {
                    return $item;
                }
            });
   
   
            if (!empty($employeeNumbers))  {
                $index = 0;
                $content =  [
                    "key"=> "employeeNumbers",
                    "defaultLabel"=> "Employee Numbers",
                    "labelKey"=> "EMPLOYEE.PERSONAL.REGEISTRATION_NUMBER",
                    "content"=> [
                        "employeeNumber",
                        "attendanceId"
                    ]
                ];
                $addStructure[$index] = $content;
               
            }
            
            DB::table('frontEndDefinition')
                ->where('id', 1)
                ->update(['structure' => json_encode($addStructure)]);

        }
        
        //edit Employee frontEnd definition 
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
                 
                $employeeNumbers= array_filter($editStructure[$personalKey]['content'], function ($item) {
                    if ($item['key'] === 'employeeNumbers') {
                        return $item;
                        }
                    });

                if (empty($employeeNumbers))  {
                    $index = 0;
                    $content =  [
                        "key"=> "employeeNumbers",
                        "defaultLabel"=> "Employee Numbers",
                        "labelKey"=> "EMPLOYEE.PERSONAL.REGEISTRATION_NUMBER",
                        "content"=> [
                          "employeeNumber",
                          "attendanceId"
                        ]
                    ];
                         
                    $editStructure[$personalKey]['content'] = array_merge(array_slice($editStructure[$personalKey]['content'], 0, $index), array($content), array_slice($editStructure[$personalKey]['content'], $index));
                }
                       
            }

            $employment = array_filter($editStructure, function ($item) {
                if ($item['key'] === 'employment') {
                    return $item;
                  }
                });
            
            if ($employment) {
                $employmentlKey = array_search('employment', array_column($editStructure, 'key'));
                $employeeNumberSectionKey = array_search('employeeNumbers', array_column($editStructure[$employmentlKey]['content'],'key'));
                $employeeNumbers= array_filter($editStructure[$employmentlKey]['content'], function ($item) {
                   
                    if ($item['key'] === 'employeeNumbers') {
                        return $item;
                        }
                    });

                if (!empty($employeeNumbers))  {
                    unset( $editStructure[$employmentlKey]['content'][$employeeNumberSectionKey]);
                         
                }
                $editStructure[$employmentlKey]['content'] = array_values($editStructure[$employmentlKey]['content']);       
            }

            DB::table('frontEndDefinition')
              ->where('id', 2)
              ->update(['structure' => json_encode($editStructure)]);

        }
    }
}
