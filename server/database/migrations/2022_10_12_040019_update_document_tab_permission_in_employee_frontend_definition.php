<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function React\Promise\Stream\first;

class UpdateDocumentTabPermissionInEmployeeFrontendDefinition extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $frontEndDefinition = DB::table('frontEndDefinition')->where('id', 2)->first(['structure']);

        $structure = json_decode($frontEndDefinition->structure, true);

        foreach ($structure as $index => &$tab) {
            if (isset($tab['key']) && $tab['key'] === 'documents') {
                $tab['viewPermission'] = ["document-manager-read-write", "document-manager-employee-access"];
                $tab['editablePermission'] = ["document-manager-read-write", "document-manager-employee-access"];
                $structure[$index] = $tab;
            }
        }

        DB::table('frontEndDefinition')->where('id', 2)->update(['structure' => json_encode($structure)]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $frontEndDefinition = DB::table('frontEndDefinition')->where('id', 2)->first(['structure']);

        $structure = json_decode($frontEndDefinition->structure, true);

        foreach ($structure as $index => &$tab) {
            if (isset($tab['key']) && $tab['key'] === 'documents') {
                $tab['viewPermission'] = "document-manager-read-write";
                $tab['editablePermission'] = "document-manager-read-write";
                $structure[$index] = $tab;
            }
        }

        DB::table('frontEndDefinition')->where('id', 2)->update(['structure' => json_encode($structure)]);
    }
}
