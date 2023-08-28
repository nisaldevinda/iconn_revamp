<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertWorkCalendarGeneralCalendarData extends Migration
{

    private $dateNamesTable;
    private $workCalendarTable;
    private $dayTypesTable;
    private $dayNamesArray;


    public function __construct()
    {
        $this->dateNamesTable = 'workCalendarDateNames';
        $this->workCalendarTable = 'workCalendar';
        $this->dayTypesTable = 'workCalendarDateType';
        $this->dayNamesArray = [
            0 => [
                'date' => 'Sunday',
                'isChecked' => false
            ],
            1 => [
                'date' => 'Monday',
                'isChecked' => true
            ],
            2 => [
                'date' => 'Tuesday',
                'isChecked' => true
            ],
            3 => [
                'date' => 'Wednesday',
                'isChecked' => true
            ],
            4 => [
                'date' => 'Thursday',
                'isChecked' => true
            ],
            5 => [
                'date' => 'Friday',
                'isChecked' => true
            ],
            6 => [
                'date' => 'Saturday',
                'isChecked' => false
            ],
        ];
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $calendarId = DB::table($this->workCalendarTable)
            ->insertGetId(['name' => 'General', 'year' => date("Y")]);

        if (!empty($calendarId)) {

            $dateTypes = DB::table($this->dayTypesTable)->select(['name', 'id'])->get();
            $dateId = 0;

            if (isset($dateTypes)) {

                foreach ($this->dayNamesArray as $key => $day) {

                    $dateId = DB::table($this->dateNamesTable)->insertGetId([
                        'name' => $day['date'],
                        'calendarId' => $calendarId,
                        'dateTypeId' => $day['isChecked'] ?  $dateTypes[0]->id : $dateTypes[1]->id
                    ]);
                }
            }
            if ($dateId > 0) {
                echo "Data Inserted";
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    // TODO : Have to check the reverse once the work calendar reverse method is created
    public function down()
    {
        try {
            foreach (array_keys($this->dayNamesArray) as $id) {
                $record = DB::table($this->table)->where('id', $id)->first();

                if ($record) {
                    DB::table($this->table)->where('id', $id)->delete();
                }
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
