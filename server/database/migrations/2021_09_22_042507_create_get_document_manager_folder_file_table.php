<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGetDocumentManagerFolderFileTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // $procedure = "DROP PROCEDURE IF EXISTS `getDocumentFileByFolderIdAndEmpId`;
        //     CREATE PROCEDURE `getDocumentFileByFolderIdAndEmpId` (IN idx int,)
        //     BEGIN
        //     SELECT * FROM posts WHERE user_id = idx;
        //     END;";
  
        // \DB::unprepared($procedure);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::dropIfExists('get_document_manager_folder_file');
    }
}
