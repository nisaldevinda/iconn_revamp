<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddDocumentTemplatesIntoFrontEndDefinitions extends Migration
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

            $docTemplate = array_filter($structure, function ($item) {
                if ($item['key'] === 'documentTemplates') {
                    return $item;
                }
            });

            // add if documentTemplates not exist
            if (empty($docTemplate)) {

                $data = [
                    "key" => "documentTemplates",
                    "defaultLabel" => "Document Templates",
                    "labelKey" => "EMPLOYEE.DOCUMENT_TEMPLATES",
                    "content" => [
                        [
                            "key" => "documentTemplates",
                            "defaultLabel" => "Document Templates",
                            "labelKey" => "EMPLOYEE.DOCUMENT_TEMPLATES",
                            "content" => [
                                "documentTemplates"
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

            $docTemplate = array_filter($structure, function ($item) {
                if ($item['key'] === 'documentTemplates') {
                    return $item;
                }
            });

            // remove if documentTemplates exist
            if (!empty($docTemplate)) {

                $docTemplates = array_filter($structure, function ($item) {
                    if ($item['key'] != 'documentTemplates') {
                        return $item;
                    }
                });

                DB::table('frontEndDefinition')
                    ->where('id', 2)
                    ->update(['structure' => json_encode($docTemplates)]);
            }
        }
    }
}
