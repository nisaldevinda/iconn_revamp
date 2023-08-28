<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFrontEndDefinitionForRegistrationNumber extends Migration
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

          $basicInformation = array_filter($addStructure, function ($item) {
            if ($item['key'] === 'basicInformation') {
              return $item;
            }
          });

           if ($basicInformation) {
              $basicInformationCardKey = -1;
              $basicInformationCardKey = array_search('basicInformation', array_column($addStructure, 'key'));

          
              if ($basicInformationCardKey > -1) {
                  $basicInformationCardContent = $addStructure[$basicInformationCardKey]['content'];
                
                  $hasEmployeeNumberField = array_search("employeeNumber", $basicInformationCardContent);
               
                  if ($hasEmployeeNumberField !== false) {
                     array_splice($basicInformationCardContent, $hasEmployeeNumberField, 1);
                  } 
               
                  $addStructure[$basicInformationCardKey]['content'] = $basicInformationCardContent;
                }
            }

            $employeeNumbers = array_filter($addStructure, function ($item) {
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
         
               $addStructure = array_merge(array_slice($addStructure, 0, $index), array($content), array_slice( $addStructure, $index));
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
                $basicInformation = array_filter($editStructure[$personalKey]['content'], function ($item) {
                    if ($item['key'] === 'basicInformation') { 
                       return $item;
                    }
                });
               
               if ($basicInformation) {
                   $basicInformationCardKey = -1;
                   $basicInformationCardKey = array_search('basicInformation', array_column($basicInformation, 'key'));
  
                  
                    if ($basicInformationCardKey > -1) {
                       $basicInformationCardContent = $basicInformation[$basicInformationCardKey]['content'];
                  
                       $hasEmployeeNumberField = array_search("employeeNumber", $basicInformationCardContent);
                 
                       if ($hasEmployeeNumberField !== false) {
                          array_splice($basicInformationCardContent, $hasEmployeeNumberField, 1);
                        } 
                
                        $editStructure[$personalKey]['content'][$basicInformationCardKey]['content'] = $basicInformationCardContent;
                    }
                }

               
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
        //add employee frontEnd definition
        $addrecord = DB::table('frontEndDefinition')->where('id', 1)->first();

       if ($addrecord) {
          $addStructure = json_decode($addrecord->structure, true);

          $basicInformation = array_filter($addStructure, function ($item) {
            if ($item['key'] === 'basicInformation') {
              return $item;
            }
          });

           if ($basicInformation) {
              $basicInformationCardKey = -1;
              $basicInformationCardKey = array_search('basicInformation', array_column($addStructure, 'key'));

          
              if ($basicInformationCardKey > -1) {
                  $basicInformationCardContent = $addStructure[$basicInformationCardKey]['content'];
                
                  $hasEmployeeNumberField = array_search("employeeNumber", $basicInformationCardContent);
               
                  if ($hasEmployeeNumberField === false) {
                      $index=0;
                      $value = 'employeeNumber';
                      $basicInformationCardContent = array_merge(array_slice($basicInformationCardContent, 0, $index), array($value), array_slice( $basicInformationCardContent, $index));
                  } 
               
                  $addStructure[$basicInformationCardKey]['content'] = $basicInformationCardContent;
                }
            }

            $employeeNumbers = array_filter($addStructure, function ($item) {
                if ($item['key'] === 'employeeNumbers') {
                  return $item;
                }
              });
            if ( $employeeNumbers)  {
                array_splice($addStructure, 0 , 1);
            }
       

           DB::table('frontEndDefinition')
            ->where('id', 1)
            ->update(['structure' => json_encode($addStructure)]);
        }

        //editEmployee frontEnd definition
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
                   $basicInformationCardKey = -1;
                   $basicInformationCardKey = array_search('basicInformation', array_column($editStructure[$personalKey]['content'], 'key'));
  
                    if ($basicInformationCardKey > -1) {
                        
                       $basicInformationCardContent = $basicInformation[$basicInformationCardKey]['content'];
                      
                       $hasEmployeeNumberField = array_search("employeeNumber", $basicInformationCardContent);
                 
                       if ($hasEmployeeNumberField === false) {
                          $index=0;
                          $value = 'employeeNumber';
                          $basicInformationCardContent = array_merge(array_slice($basicInformationCardContent, 0, $index), array($value), array_slice( $basicInformationCardContent, $index));
                        } 
                
                        $editStructure[$personalKey]['content'][$basicInformationCardKey]['content'] = $basicInformationCardContent;
                    }
                }

               
                $employeeNumbers = array_filter($editStructure[$personalKey]['content'], function ($item) {
                    if ($item['key'] === 'employeeNumbers') {
                        return $item;
                        }
                    });

                if ($employeeNumbers)  {
                   
                    array_splice( $editStructure[$personalKey]['content'], 0 , 1);   
                  
                }
                       
            }

            DB::table('frontEndDefinition')
              ->where('id', 2)
              ->update(['structure' => json_encode($editStructure)]);

        }
    }
}
