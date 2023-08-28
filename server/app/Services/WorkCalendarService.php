<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Traits\JsonModelReader;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Name: Work Calendar Date Type Service
 * Purpose: Performs tasks related to the Work Calendar model.
 * Description:  Work Calendar Date Type Service class is called by the  WorkCalendarWorkCalendarController 
 * where the requests related code logics are processed
 * Module Creator: Yohan
 */

class WorkCalendarService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $workCalendarTableName;
    private $dateNamesTableName;
    private $dateTypesTableName;
    private $specialDaysTableName;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->workCalendarTableName = 'workCalendar';
        $this->dateNamesTableName = 'workCalendarDateNames';
        $this->dateTypesTableName = 'workCalendarDayType';
        $this->specialDaysTableName = 'workCalendarSpecialDays';
    }

    /**
     * Following function creates a new work calendar.
     *
     * @param $WorkCalendar array containing the work calendar data
     * @return int | String | array | object 
     *
     * Usage:
     * $WorkCalendar => ["name": "Working Day"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "date type created Successuflly",
     * $data => {"name": "Working Day"}//$data has a similar set of values as the input
     *  
     * */
    public function createWorkCalendar($workCalendar)
    {
        try {

            if (isset($workCalendar['name'])) {

                if ($this->isCalendarNameAvailable($workCalendar['name'])) {
                    $data = [
                        'isUnique' => true
                    ];
                    return $this->error(400, Lang::get('workCalendarMessages.basic.ERR_IS_EXISTING'), $data);
                }

                $calendarId = DB::table($this->workCalendarTableName)
                    // ->insertGetId(['name' => $workCalendar['name'], 'year' => date("Y")]);
                    ->insertGetId(['name' => $workCalendar['name']]);
            }

            if (!empty($calendarId) || !is_null($workCalendar['check'])) {

                $dateTypes = DB::table($this->dateTypesTableName)->select(['name', 'id'])->get();
                $dateId = 0;
                foreach ($workCalendar['check'] as $key => $day) {

                    $dayObject = new Carbon($day['date']);
                    $dayId = $dayObject->dayOfWeek;
                    $dateId = DB::table($this->dateNamesTableName)->insertGetId([
                        // 'name' => $day['date'],
                        'dayOfWeekId' =>  $dayId,
                        'calendarId' => $calendarId,
                        'workCalendarDayTypeId' => $day['isChecked'] ?  $dateTypes[0]->id : $dateTypes[1]->id
                    ]);
                }

                $addedCalendar = DB::table($this->workCalendarTableName)->where('id', $calendarId)->select()->get();

                // rollback changes if the dateNames are not inserted

                if (is_null($dateId) || $dateId == 0) {

                    $lastCalendar = DB::table($this->workCalendarTableName)->orderBy('id', 'desc')->first();

                    DB::table($this->workCalendarTableName)->where('id', $lastCalendar->id)->delete();
                }


                if (!is_null($dateId)) {
                    return $this->success(201, Lang::get('workCalendarMessages.basic.SUCC_CREATE'), $addedCalendar);
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workCalendarMessages.basic.ERR_CREATE'), null);
        }
    }

    /**
     * Following function creates a new speical day in the speical day table.
     *
     * @param $specialDay array containing the speical day data
     * @return int | String | array | object 
     *
     * Usage:
     * $specialDay => ["name": "Working Day"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "speical day created Successuflly",
     * $data => {1}//$data has a similar set of values as the input
     *  
     * */
    public function creatSpecialDate($specialDay)
    {
        try {

            //check whether record exsisting for same calender and same date
            $record = DB::table($this->specialDaysTableName)->where('calendarId', $specialDay['calendarId'])->where('date', $specialDay['date'])->first();
            $dayOfWeek = Carbon::parse($specialDay['date'])->dayOfWeek; 
            $dateTypeId = null;

            //get default day type for this date
            $defaltDayTypeData = DB::table('workCalendarDateNames')
                ->where('calendarId', $specialDay['calendarId'])
                ->where('dayOfWeekId', $dayOfWeek)->first();

            $defaltDayTypeId = $defaltDayTypeData->workCalendarDayTypeId;
           
            if (!empty($record)) {
                $record = (array) $record;

                if ($defaltDayTypeId === $specialDay['dateTypeId']) {
                    $deleteSpecialDay = DB::table($this->specialDaysTableName)->where('id', $record['id'])->delete();
                } else {
                    $dateTypeId = DB::table($this->specialDaysTableName)->where('id', $record['id'])
                        ->update(['workCalendarDayTypeId' => $specialDay['dateTypeId']]);
                }

            } else {

                if ($defaltDayTypeId !== $specialDay['dateTypeId']) {
                    $dateTypeId = DB::table($this->specialDaysTableName)->insertGetId([
                        'calendarId' => $specialDay['calendarId'],
                        'date' => $specialDay['date'],
                        'workCalendarDayTypeId' => $specialDay['dateTypeId']
                    ]);
                }

            }

 
            $workCalendar = $this->store->getFacade()::table($this->workCalendarTableName)
                ->where('id', $specialDay['calendarId'])
                ->update(['updatedAt' => Carbon::now()->toDateTimeString()]);
            
            return $this->success(201, Lang::get('workCalendarMessages.basic.SUCC_SPECIAL_DAY'), $dateTypeId);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workCalendarMessages.basic.ERR_SPECIAL_DAY'), null);
        }
    }

    /**
     * Following function can be used to fetch a calendar list.
     * 
     * @return int | String | array | object 
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Calendar list loaded successfully",
     * $data => [
     * {
     * "key": 0,
     * "menuItemName": "General",
     * "calendarId": 1,
     * "year": "2022",
     * "month": "Jan"
     * } 
     * ]
     *  
     * */
    public function getCalendarList()
    {
        try {

            $calendarLists = $this->store->getFacade()::table($this->workCalendarTableName)->get();

            if (is_null($calendarLists)) {
                return $this->error(404, Lang::get('workCalendarMessages.basic.ERR_NONEXISTENT_WORK_CALENDAR_DATE_TYPE'), $calendarLists);
            }

            $restructuredCalendarList = [];

            foreach ($calendarLists as $key => $calendarList) {
                $restructuredCalendarList[$key] = [
                    'key' => $key,
                    'menuItemName' => $calendarList->name,
                    'calendarId' => $calendarList->id,
                    'year' => date('Y'),
                    'month' =>  date('M')
                ];
            }
            $restructuredCalendarListObject = json_decode(json_encode($restructuredCalendarList), FALSE);

            return $this->success(200, Lang::get('workCalendarMessages.basic.SUCC_CALLIST_RETRIVE'), $restructuredCalendarListObject);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workCalendarMessages.basic.ERR_CALLIST_RETRIVE'), null);
        }
    }

    /**
     * Following function can be used to fetch calendar object for a given calendar id .
     * @param $calanderId number containing the calendar id 
     * @return int | String | array | object 
     *
     * Sample output: [
     * 0=> {
     *   calendarName": "General"
     *   calendarYear": "2022",
     *   createdAt": "2022-01-12 15:53:07"
     *   dayName": "Sunday"
     *   dayType": "Non Working Day"
     *   dayTypeId": 2
     *   color": "warning"
     * }]
     * 
     * */
    private function getCalendarData($calanderId)
    {
        try {

            if (is_null($calanderId)) {
                return $this->error(404, Lang::get('workCalendarMessages.basic.NULL_CALID'), $calanderId);
            }

            $calendarLists = DB::select('
                    SELECT 
                    wc.name AS calendarName, wc.id AS calendarId, wc.createdAt AS createdAt, wc.updatedAt AS updatedAt,
                    CONCAT(UCASE(MID(dow.dayName,1,1)),LCASE(MID(dow.dayName,2))) AS dayName,  wcdn.id AS dayId, 
                    wcdt.name AS dayType, wcdt.id AS dayTypeId, wcdt.typeColor AS color  
                    FROM workCalendar wc 
                    INNER JOIN workCalendarDateNames wcdn ON wc.id =wcdn.calendarId
                    LEFT JOIN dayOfWeek dow  ON dow.id = wcdn.dayOfWeekId
                    INNER JOIN workCalendarDayType wcdt  ON wcdt.id = wcdn.workCalendarDayTypeId
                    WHERE wc.id = ' . $calanderId . ';
            ');

            if (is_null($calendarLists)) {
                return $this->error(404, Lang::get('workCalendarMessages.basic.NULL_CALLIST'), $calendarLists);
            }

            $restructuredCalendarList = [];

            foreach ($calendarLists as $key => $calendarList) {

                $restructuredCalendarList[$key] = [
                    'calendarId' => $calendarList->calendarId,
                    'calendarName' => $calendarList->calendarName,
                    // 'calendarYear' => $calendarList->calendarYear,
                    'createdAt' => $calendarList->createdAt,
                    'updatedAt' => $calendarList->updatedAt,
                    'dayName' => $calendarList->dayName,
                    'dayType' => $calendarList->dayType,
                    'dayTypeId' => $calendarList->dayTypeId,
                    'color' => $calendarList->color
                ];
            }

            $restructuredCalendarListObject = json_decode(json_encode($restructuredCalendarList), FALSE);

            return $restructuredCalendarListObject;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workCalendarMessages.basic.ERR_CALLDATA_RETRIVE'), null);
        }
    }

    /**
     * Following function can be used to fetch special days assinged for a given calendarId.
     * @param $calanderId number containing the calendar id 
     * @return int | String | array | object 
     *
     * Sample output: [
     * 0=> {
     *   calendarDate": "2022-01-02"
     *   dateType": "Non Working Day",
     *   dateTypeColor": "warning"
     * }]
     * 
     * */
    private function getSpecialDays($calendarId)
    {
        try {

            $specialDayList = DB::select('
                        SELECT 
                        wcsd.date AS calendarDate, 
                        wcdt.name AS dateType, wcdt.typeColor as dateTypeColor 
                        FROM workCalendarSpecialDays wcsd 
                        INNER JOIN workCalendarDayType wcdt ON wcdt.id  = wcsd.workCalendarDayTypeId 
                        WHERE wcsd.calendarId  = ' . $calendarId . ';     
            ');

            return $specialDayList;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return [];
        }
    }


    /**
     * Following function is used to get a date list for a given month and year.
     * @param $month = the month name example Jan or July
     * @param $year = the year value example 2022,
     * @param $calendar = the calendar object containing sub properties like calendarId, etc,
     * @param $specialDays = the specialDays object containing sub properties like dayType, etc,
     * @return int | String | array | object 
     *
     * Sample output: [
     * 0=> {
     *   date": "2022-01-02"
     *   dayType": "Non Working Day",
     *   dayTypeId": 2,
     *   dayTypeColor": "warning"
     * }]
     * */
    private function getDateList($month, $year, $calendar, $specialDays)
    {
        try {

            $monthInt =  (int) $month;
            $yearInt = (int) $year;
            $monthNumber = date("m", strtotime($month));
            $monthObject = Carbon::createFromFormat('Y-m', "$yearInt-$monthNumber");
            $numberOfDaysInMonth = $monthObject->daysInMonth;
            $allDates = array(); // non mutated date list
            $mutatedDateList = array(); // mutated date list

            if (is_null($month) || is_null($year) || is_null($calendar)) {

                return $this->error(404, Lang::get('workCalendarMessages.basic.ERR_META_DATA'), null);
            }

            // loop to make the date list
            for ($i = 1; $i < $numberOfDaysInMonth + 1; ++$i) {

                $allDates[] = Carbon::createFromDate($yearInt, $monthNumber, $i)->format('Y-m-d'); // F-d-Y = format if want in words

            }

            // loop to add working and non working day dates for a month 
            foreach ($allDates as $dateIndex => $date) {
                foreach ($calendar as $calDataIndex => $calData) {

                    $dateName = date('l', strtotime($date));
                    if ($dateName == $calData->dayName) {
                        $mutatedDateList[] = [
                            "date" => $allDates[$dateIndex],
                            "dayType" => $calData->dayType,
                            'dayTypeId' => $calData->dayTypeId,
                            "dayTypeColor" => $calData->color
                        ];
                    }
                }
            }

            if (isset($specialDays)) {

                foreach ($mutatedDateList as $mutatedDateIndex => $mutatedDate) {
                    foreach ($specialDays as $specialDayIndex => $specialDay) {
                        if ($specialDay->calendarDate == $mutatedDate['date']) {

                            $mutatedDateList[$mutatedDateIndex] = [
                                "date" => $specialDay->calendarDate,
                                "dayType" => $specialDay->dateType,
                                "dayTypeColor" => $specialDay->dateTypeColor
                            ];
                        }
                    }
                }
            }

            return $mutatedDateList;
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * Following function acts as a warp function to combine all the created sub functions 
     * and is called by the exposed API.
     * 
     * @param $calendarMetaData = object containing key value pairs such as calendarId
     * @return int | String | array | object 
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Calendar metadata loaded successfully",
     * $data => [
     * {
     * "date": 2022-01-01,
     * "dayType": "Non Working Day",
     * "dayTypeId": 2,
     * "dayTypeColor": "warning",
     * } 
     * ]
     * */
    public function getCalendarMetaData($calendarMetaData)
    {
        $calendarId = $calendarMetaData['calendarId'];
        $currentMonth = $calendarMetaData['month'];
        $currentYear = $calendarMetaData['year'];
        $calendarObject = $this->getCalendarData($calendarId);
        $specialDays =   $this->getSpecialDays($calendarId);
        $calendarMetaData = $this->getDateList($currentMonth, $currentYear, $calendarObject, $specialDays);

        if (!is_null($calendarMetaData)) {
            return $this->success(200, Lang::get('workCalendarMessages.basic.SUCC_CALMETA_RETRIVE'), $calendarMetaData);
        }

        return $this->error(404, Lang::get('workCalendarMessages.basic.ERR_CALMETA_RETRIVE'), null);
    }


    /**
     * Following function creates a summery of a given calendar object and year which includes data such as
     * working days non working days and created day of the respective calendar
     * 
     * @param $year = 2022
     * @param $calendar = Calendar object with key value pairs
     * @return int | String | array | object 
     *
     * Sample output:[ 
     *   workingDays": 261
     *   nonWorkingDays": 104,
     *   calendarCreatedOn": "2022-01-12",
     * ]
     * */
    private function getCalendarSummeryInfo($year, $calendar, $specialDays, $dateTypes)
    {
        $months = array();
        $allDatesObject = array();
        $mutatedDateList = array(); // mutated date list
        $calendarList = array();

        // adding all 12 months to an array
        for ($m = 1; $m <= 12; $m++) {
            $months[] = date('M', mktime(0, 0, 0, $m, 1, date('Y')));
        }

        // loop to annual date range
        foreach ($months as $key => $month) {
            $calendarList[$month] = $this->getDateList($month, $year, $calendar, $specialDays);
        }

        // mapping all the 12 months dates to a single dates array
        foreach ($calendarList as $calendarListIndex => $calendarMetaData) {
            foreach ($calendarMetaData as $calendarMetaDataIndex => $calendarDateObject) {
                $allDatesObject[] = $calendarDateObject;
            }
        }

        $datesTypes = [];

        // loop to add custom dates for an year 
        foreach ($allDatesObject as $dateIndex => $date) {
            foreach ($dateTypes as $dateTypeObjects) {
                if (!$dateTypeObjects->isDelete && $date['dayType'] == $dateTypeObjects->name) {
                    $datesTypes[$dateTypeObjects->name][] = $date['dayType'];
                };
            }
            $mutatedDateList[$date['dayType']] = (object) [
                "color" =>  $date['dayTypeColor'],
                "dayCount" => count($datesTypes[$date['dayType']])
            ];
        }

        if (is_null($mutatedDateList) || is_null($calendar)) {
            return $this->error(404, Lang::get('workCalendarMessages.basic.ERR_SUMMERY_DATA'), null);
        }

        $summeryData = [
            "calendarCreatedOn" => date('d-m-Y', strtotime($calendar[0]->updatedAt)),
            "dateTypes" => (array) $mutatedDateList
        ];

        return $summeryData;
    }

    /**
     * Following function acts as a warp function to combine the sub functions created to generate the calendar
     * summery. This function is also responsibly for API exposition
     * 
     * @param $calendarSummeryData = Object with key value pairs
     * @return int | String | array | object 
     * Sample output:
     * $statusCode => 200,
     * $message => "Calendar summery data loaded successfully",
     * $data => {
     * "workingDays": 261,  
     * "nonWorkingDays": 104,
     * "calendarCreatedOn": "2022-01-12",
     * } 
     * */
    public function getCalendarSummmery($calendarSummeryData)
    {
        $calendarId = $calendarSummeryData['calendarId'];
        $currentYear = $calendarSummeryData['year'];
        $calanderObject = $this->getCalendarData($calendarId);
        $speicalDays = $this->getSpecialDays($calendarId);
        $dateTypes = $this->store->getFacade()::table($this->dateTypesTableName)->get();

        $calendarSummeryData = $this->getCalendarSummeryInfo($currentYear, $calanderObject, $speicalDays, $dateTypes);

        if (!is_null($calendarSummeryData)) {
            return $this->success(200, Lang::get('workCalendarMessages.basic.SUCC_SUMMERY_DATA'), $calendarSummeryData);
        }

        return $this->error(404, Lang::get('workCalendarMessages.basic.ERR_SUMMERY_DATA'), null);
    }

    /**
     * Following function can be used to get a list of the created date types
     * 
     * @return int | String | array | object 
     * Sample output:
     * $statusCode => 200,
     * $message => "Date types data loaded successfully",
     * $data => [
     * {
     * "id": 2022-01-01,
     * "name": "Working Day",
     * "typeColor": "success,
     * } 
     * ]
     * */
    public function getCalendarDateTypes()
    {
        try {

            $dateTypes = $this->store->getFacade()::table($this->dateTypesTableName)
               ->where('isDelete', 0)
               ->get();

            if (is_null($dateTypes)) {
                return $this->error(404, Lang::get('workCalendarMessages.basic.ERR_DATE_TYPE_DATA'), $dateTypes);
            }

            return $this->success(200, Lang::get('workCalendarMessages.basic.SUCC_DATE_TYPE_DATA'), $dateTypes);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workCalendarMessages.basic.ERR_DATE_TYPE_DATA'), null);
        }
    }

    public function getEmployeeCalendar($employeeId)
    {
        try {
            $calendarId = 1;

            $dateTypes = $this->store->getFacade()::table('employee')
                ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                ->where('employee.id', $employeeId)
                ->select('employeeJob.calendarId')
                ->first();

            if (!empty($dateTypes) && !empty($dateTypes->calendarId)) {
                $calendarId = $dateTypes->calendarId;
            }

            return $this->getCalendarData($calendarId);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workCalendarMessages.basic.ERR_DATE_TYPE_DATA'), null);
        }
    }

    public function editCalendarName($id, $calendar)
    {
        try {

            $dbCalendar = $this->store->getFacade()::table($this->workCalendarTableName)->where('id', $id)->first();

            if (is_null($dbCalendar)) {
                return $this->error(404, Lang::get('workCalendarMessages.basic.ERR_NONEXISTENT_CAL'), $calendar);
            }

            if ($this->isCalendarNameAvailable($calendar['name'])) {
                return $this->error(400, Lang::get('workCalendarMessages.basic.ERR_IS_EXISTING'), null);
            }

            if (empty($calendar['name'])) {
                return $this->error(400, Lang::get('workCalendarMessages.basic.ERR_NONEXISTENT_CAL'), $calendar);
            }

            $this->store->getFacade()::table($this->workCalendarTableName)->where('id', $id)->update(['name' => $calendar['name'],'updatedAt' => Carbon::now()->toDateTimeString() ]);

            return $this->success(200, Lang::get('workCalendarMessages.basic.SUCC_UPDATE_NAME'), $calendar);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('workCalendarMessages.basic.ERR_DATE_TYPE_DATA'), null);
        }
    }


    private function isCalendarNameAvailable($name)
    {
        $calendarObject = $this->store->getFacade()::table($this->workCalendarTableName)
            ->where('name', $name)->first();
        return !empty($calendarObject);
    }
}
