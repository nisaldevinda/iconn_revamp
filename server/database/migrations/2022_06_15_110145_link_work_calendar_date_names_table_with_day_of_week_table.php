<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LinkWorkCalendarDateNamesTableWithDayOfWeekTable extends Migration
{

    private $dateNamesTable;
    private $dayNamesArray;


    public function __construct()
    {
        $this->dateNamesTable = 'workCalendarDateNames';
        $this->dayNamesArray = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday'
        ];
    }




    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $dateNamesTableRecords = DB::table($this->dateNamesTable)->whereIn('name', $this->dayNamesArray)->get()->toArray();

        if (!empty($dateNamesTableRecords)) {
            foreach ($dateNamesTableRecords as $key => $dayRecord) {
                $dayRecord = (array) $dayRecord;
                $dayNameId = (!empty($dayRecord['name'])) ? array_search($dayRecord['name'], $this->dayNamesArray) : null;

                //update name filed with related id of dayOfWeek Table
                $dateId = DB::table($this->dateNamesTable)->where('id', $dayRecord['id'])
                    ->update(['name' => $dayNameId]);
            }

            Schema::table($this->dateNamesTable, function (Blueprint $table) {
                $table->renameColumn('name','dayOfWeekId')->change();
            });
    
            Schema::table($this->dateNamesTable, function (Blueprint $table) {
                $table->integer('dayOfWeekId')->nullable()->default(null)->change();
            });

        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table($this->dateNamesTable, function (Blueprint $table) {
            $table->string('dayOfWeekId')->nullable()->default(null)->change();
        });

        $dateNamesTableRecords = DB::table($this->dateNamesTable)->get()->toArray();

        if (!empty($dateNamesTableRecords)) {
            foreach ($dateNamesTableRecords as $key => $dayRecord) {
                $dayRecord = (array) $dayRecord;
                $dayName = (!is_null($dayRecord['dayOfWeekId'])) ? $this->dayNamesArray[$dayRecord['dayOfWeekId']] : null;

                //update name filed with related id of dayOfWeek Table
                $dateId = DB::table($this->dateNamesTable)->where('id', $dayRecord['id'])
                    ->update(['dayOfWeekId' => $dayName]);
            }

            Schema::table($this->dateNamesTable, function (Blueprint $table) {
                $table->renameColumn('dayOfWeekId', 'name')->change();
            });
        }
    }
}
