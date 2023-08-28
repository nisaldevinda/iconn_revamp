<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertShortCodesForAlreadyDayType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $workCalendarDayTypes = DB::table('workCalendarDayType')->get()->toArray();

        if (!empty($workCalendarDayTypes)) {
            foreach ($workCalendarDayTypes as $key => $dayType) {
                $dayType = (array) $dayType;

                switch ($dayType['name']) {
                    case 'Working Day':
                        $shortCode = "WD";
                        break;
                    case 'Non Working Day':
                        $shortCode = "NWD";
                        break;
                    case 'Holiday':
                        $shortCode = "HD";
                        break;
                    default:
                        $shortCode = null;
                        break;
                }

                $dateId = DB::table('workCalendarDayType')->where('id', $dayType['id'])
                    ->update(['shortCode' => $shortCode]);
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
        $workCalendarDayTypes = DB::table('workCalendarDayType')->get()->toArray();

        if (!empty($workCalendarDayTypes)) {
            foreach ($workCalendarDayTypes as $key => $dayType) {
                $dayType = (array) $dayType;
                $shortCode = null;
                $dateId = DB::table('workCalendarDayType')->where('id', $dayType['id'])
                    ->update(['shortCode' => $shortCode]);
            }
        }
    }
}
