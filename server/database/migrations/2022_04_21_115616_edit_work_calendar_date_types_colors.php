<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditWorkCalendarDateTypesColors extends Migration
{
    private $dateTypesTableName = 'workCalendarDateType';

    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        $currentRecords = array();

        for ($count = 1; $count <= 3; $count++) {
            $currentRecords[] = DB::table($this->dateTypesTableName)->where('id', $count)->first();
        }

        foreach ($currentRecords as $currentRecordsIndex =>  $currentRecord) {
            switch ($currentRecord->typeColor) {
                case 'success':
                    $currentRecords[$currentRecordsIndex]->typeColor = '#E5E5E5';
                    break;
                case 'warning':
                    $currentRecords[$currentRecordsIndex]->typeColor = '#FFC53D';
                    break;
                case 'error':
                    $currentRecords[$currentRecordsIndex]->typeColor = '#FF701E';
                    break;
            }
        }

        foreach ($currentRecords as $updatedQuery) {
            DB::table($this->dateTypesTableName)->where('id', $updatedQuery->id)
                ->update(['typeColor' => $updatedQuery->typeColor]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $currentRecords = array();

        for ($count = 1; $count <= 3; $count++) {
            $currentRecords[] = DB::table($this->dateTypesTableName)->where('id', $count)->first();
        }

        foreach ($currentRecords as $currentRecordsIndex =>  $currentRecord) {
            switch ($currentRecord->typeColor) {
                case '#E5E5E5':
                    $currentRecords[$currentRecordsIndex]->typeColor = 'success';
                    break;
                case '#FFC53D':
                    $currentRecords[$currentRecordsIndex]->typeColor = 'warning';
                    break;
                case '#FF701E':
                    $currentRecords[$currentRecordsIndex]->typeColor = 'error';
                    break;
            }
        }

        foreach ($currentRecords as $updatedQuery) {
            DB::table($this->dateTypesTableName)->where('id', $updatedQuery->id)
                ->update(['typeColor' => $updatedQuery->typeColor]);
        }
    }
}
