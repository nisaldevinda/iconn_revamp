<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDocumentManagerToFrontEndDefinitionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       // get edit definition
       $record = DB::table('frontEndDefinition')->where('id', 2)->first();

       if ($record) {
           $structure = json_decode($record->structure, true);

           $documents = array_filter($structure, function ($item) {
               if ($item['key'] === 'documents') {
                   return $item;
               }
           });

           // add if documents not exist
           if (empty($documents)) {

               $data = [
                   "key" => "documents",
                   "defaultLabel" => "Documents",
                   "labelKey" => "EMPLOYEE.DOCUMENTS",
                   "content" => [
                       [
                           "key" => "documents",
                           "defaultLabel" => "Documents",
                           "labelKey" => "EMPLOYEE.DOCUMENTS",
                           "content" => [
                               "documents"
                           ]
                       ]
                   ]
               ];
               array_push($structure, $data);

               DB::table('frontEndDefinition')
                   ->where('id', 2)
                   ->update(['structure' => json_encode($structure)]);
           }
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
       $record = DB::table('frontEndDefinition')->where('id', 2)->first();

       if ($record) {
           $structure = json_decode($record->structure, true);

           $documents = array_filter($structure, function ($item) {
               if ($item['key'] === 'documents') {
                   return $item;
               }
           });

           // remove if documents exist
           if (!empty($documents)) {

                $data = array_filter($structure, function ($item) {
                   if ($item['key'] != 'documents') {
                       return $item;
                   }
               });

               DB::table('frontEndDefinition')
                   ->where('id', 2)
                   ->update(['structure' => json_encode($data)]);
           }
       }
   
    }
}
