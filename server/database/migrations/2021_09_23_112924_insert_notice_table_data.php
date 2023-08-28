<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertNoticeTableData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $noticeData = array(
            [
                'id' => 1,
                'topic' => 'Urgent Meeting',
                'description' => 'Urgent Meeting description',
                'status' => 'Unpublished',
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ],
            [
                'id' => 2,
                'topic' => 'Coffee Meeting',
                'description' => 'Coffee Meeting description',
                'status' => 'Published',
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
            ]
        );
        DB::table('notice')->insert($noticeData);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('notice')->where('id', [1, 2])->delete();
    }
}
