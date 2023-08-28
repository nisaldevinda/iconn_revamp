<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Traits\JsonModelReader;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log;

/**
 * php artisan db:seed --class=BulkDataSeeder
 */
class BulkDataSeeder extends Seeder
{
    private $jobTitles = array();
    private $locations = array();
    private $locationTableName = 'location';
    private $jobTitleTableName = 'jobTitle';
    private $genderTableName = 'gender';
    private $employeeTableName = 'employee';
    private $userTableName = 'user';
    private $employeeJobTableName = 'employeeJob';
    private $locationWrkPtn = [];
    private $dataRange = 5000; // change this line to change the data count
    private $employeeRole = null; // change this line for diffrent user roles

    use JsonModelReader;

    public function __construct()
    {
        $empRole = DB::table('userRole')->where('type', 'EMPLOYEE')->first();

        if (empty($empRole)) {
            Log::error("Please create employee role first.");
            exit();
        }

        $this->employeeRole = $empRole->id;

        $this->jobTitles = array(
            [
                'id' => 1,
                'name' => 'Software Enginner'
            ],
            [
                'id' => 2,
                'name' => 'Dev Ops Enginner'
            ],
            [
                'id' => 3,
                'name' => 'QA Engineer'
            ],
            [
                'id' => 4,
                'name' => 'Business Analyst'
            ],
            [
                'id' => 5,
                'name' => 'Data Analyst'
            ],
        );

        $this->locations = array(
            [
                'id' => 1,
                'name' => 'Colombo',
                'stateProvinceId' => '3865',
                'countryId' => '211',
                'timeZone' => 'Asia/Colombo'
            ],
            [
                'id' => 2,
                'name' => 'NewYork',
                'stateProvinceId' => '4679',
                'countryId' => '236',
                'timeZone' => 'America/New_York'
            ],
        );
    }

    private function insertJobTitles()
    {
        try {

            foreach ($this->jobTitles as $jobTitleIndex => $jobTitle) {
                $hasJobTitle = DB::table($this->jobTitleTableName)->where('id', $jobTitle['id'])->first();

                if (!$hasJobTitle) {
                    return  DB::table($this->jobTitleTableName)->insert($this->jobTitles); // check if exists before inserting
                }

                return [];
            }
        } catch (\Throwable $th) {
            return null;
            throw $th;
        }
    }

    private function insertLocations()
    {
        try {

            foreach ($this->locations as $locationIndex => $location) {
                $hasLocation = DB::table($this->locationTableName)->where('id', $location['id'])->first();

                if (!$hasLocation) {
                    return  DB::table($this->locationTableName)->insert($this->locations);
                }

                return [];
            }
        } catch (\Throwable $th) {
            return null;
            throw $th;
        }
    }

    private function getManditoyData()
    {
        try {

            $jobTitleIds =  DB::table($this->jobTitleTableName)->select('id')->get();
            $locationIds =  DB::Table($this->locationTableName)->select('id')->get();
            $gendersIds = DB::Table($this->genderTableName)->select('id')->get();

            $jobTitleIdArray = array();
            $locationIdArray = array();
            $genderIdArray = array();

            if (!empty($jobTitleIds) && !empty($locationIds) && !empty($gendersIds)) {

                foreach ($jobTitleIds as $jobTitleId) {
                    $jobTitleIdArray[] = $jobTitleId->id;
                }

                foreach ($locationIds as $locationId) {
                    $locationIdArray[] = $locationId->id;
                }

                foreach ($gendersIds as $genderId) {
                    $genderIdArray[] = $genderId->id;
                }

                return [
                    'jobTitles' => $jobTitleIdArray,
                    'locations' => $locationIdArray,
                    'genders' => $genderIdArray
                ];
            }
        } catch (\Throwable $th) {
            return null;
            throw $th;
        }
    }

    private  function strctureEmployeeData()
    {
        $manditoryData  = $this->getManditoyData();
        $employees = array();

        if (!empty($manditoryData)) {

            foreach (range(1, $this->dataRange) as $index) {
                $faker = Faker::create();

                $employees[] = [
                    'id' => $index,
                    'employeeNumber' => 'E' . $index,
                    'firstName' => $faker->unique()->firstName(),
                    'lastName' => $faker->lastName(),
                    'dateOfBirth' => $faker->date('Y-m-d', 'now'),
                    'genderId' => $manditoryData['genders'][array_rand($manditoryData['genders'], 1)],
                    'workEmail' => strtolower($faker->unique()->firstName()) . $index . "@" . $faker->safeEmailDomain(),
                    'mobilePhone' => '94-1111111111',
                    'hireDate' => $faker->date('Y-m-d', 'now'),
                    'isActive' => true,
                    "createdAt" => null,
                    "updatedAt" => null
                ];
            }
        }

        return $employees;
    }

    private function insertEmployeesUsersAndJobs()
    {
        try {
            $employees = $this->strctureEmployeeData();
            $manditoryData = $this->getManditoyData();
            $employeeJobs = array();
            $users = array();
            $userInsertedState = false;


            DB::disableQueryLog();

            foreach ($employees as $employeeIndex => $employee) {
                $employeeId = DB::table($this->employeeTableName)->insertGetId($employees[$employeeIndex], 'id');
                $insertedEmployees = DB::table($this->employeeTableName)->where('id', $employeeId)->get();

                // creating the job data and user structure to insert a new employee job and user
                foreach ($insertedEmployees as $insertedEmployeeIndex => $insertedEmployee) {
                    $employeeJobs[] = [
                        'id' => $insertedEmployee->id,
                        'employeeId' => $insertedEmployee->id,
                        'effectiveDate' => $insertedEmployee->hireDate,
                        'locationId' => $manditoryData['locations'][array_rand($manditoryData['locations'], 1)],
                        'jobTitleId' => $manditoryData['jobTitles'][array_rand($manditoryData['jobTitles'], 1)],
                        'calendarId' => 1
                    ];

                    $users[] = [
                        'id' => 2 + $insertedEmployee->id,
                        'email' => $insertedEmployee->workEmail,
                        'firstName' => $insertedEmployee->firstName,
                        'lastName' => $insertedEmployee->lastName,
                        'employeeRoleId' => $this->employeeRole, 
                        'employeeId'=> $insertedEmployee->id,
                        'password' => '$2y$10$GzKpD.TTNWU88aEPuqvD4OsNyxnb1kyqclKh9JU4kKfGUhnWwwXBi'
                    ];
                }
            }

            // inserting the specific employee jobs and updating the employee current job id
            foreach ($employeeJobs as $employeeJobIndex => $employeeJob) {
                $insertEmployeeJobId =  DB::table($this->employeeJobTableName)
                    ->insertGetId($employeeJobs[$employeeJobIndex], 'id');

                // updating the current job Id
                DB::table($this->employeeTableName)
                    ->where('id', $employeeJobs[$employeeJobIndex]['employeeId'])
                    ->update(['currentJobsId' => $insertEmployeeJobId]);

                $this->generateAttendance($employeeJobs[$employeeJobIndex]['employeeId'], $employeeJobs[$employeeJobIndex]['locationId']);
            }

            // // inserting the users to the db
            foreach ($users as $usersIndex => $user) {
                $userInsertedState = DB::table($this->userTableName)->insert($users[$usersIndex]);
            }

            return $userInsertedState;
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
        }
    }

    private function generateAttendance($employeeId, $locationId)
    {
        Log::info("Attendance for employee > " . $employeeId);

        $today = Carbon::now();

        $period = CarbonPeriod::create('2021-03-01', $today->format('Y-m-d'));

        $locationIndex = array_search($locationId, array_column($this->locations, 'id'));

        $timeZone = $this->locations[$locationIndex]['timeZone'];

        // Iterate over the period
        foreach ($period as $date) {

            $currentDate = $date->format('Y-m-d');
            $inHour = str_pad(rand(7, 9), 2, '0', STR_PAD_LEFT);
            $outHour = rand(17, 20);
            $inMin = rand(11, 59);
            $outMin = rand(11, 59);

            $actualIn = "$currentDate $inHour:$inMin:00";
            $actualOut = "$currentDate $outHour:$outMin:00";

            $workTime = rand(480, 540);
            $breakTime = rand(0, 60);

            $attendanceRecord = [
                'date' => $currentDate,
                'employeeId' => $employeeId,
                'timeZone' => $timeZone,
                'dayTypeId' => 1,
                'shiftId' => 1,
                'hasMidnightCrossOver' => false,
                'isExpectedToPresent' => 1,
                'expectedLeaveAmount' => 1,
                'expectedIn' => $currentDate . " 08:00",
                'expectedOut' => $currentDate . " 17:00",
                'actualIn' => $actualIn,
                'actualInUTC' => $actualIn,
                'actualOut' => $actualOut,
                'actualOutUTC' => $actualOut,
                'isPresent' => 1,
                'isLateIn' => false,
                'isEarlyOut' => false,
                'workTime' => $workTime,
                'breakTime' => $breakTime,
                'isFullDayLeave' => false,
                'isHalfDayLeave' => false,
                'isShortLeave' => false,
                'isNoPay' => false
            ];

            $summaryId = DB::table('attendance_summary')->insertGetId($attendanceRecord);

            $attendance = [
                'date' => $currentDate,
                'in' => $actualIn,
                'inUTC' => $actualIn,
                'out' => $actualOut,
                'outUTC' => $actualOut,
                'typeId' => 0,
                'employeeId' => $employeeId,
                'calendarId' => 1,
                'shiftId' => 1,
                'summaryId' => $summaryId,
                'timeZone' => $timeZone,
                'earlyOut' => 0,
                'lateIn' => 0,
                'workedHours' => $workTime,
                'breakHours' => $breakTime

            ];

            DB::table('attendance')->insert($attendance);
        }
    }


    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {
            $insertedJobTitles = $this->insertJobTitles();
            $insertedLocations = $this->insertLocations();

            $locationIds = DB::Table($this->locationTableName)->select('id')->pluck('id');

            foreach ($locationIds as $locationId) {
                $locPattern = DB::table('workPatternLocation')->where('locationId', $locationId)->first();
                if (empty($locPattern)) {
                    Log::error("Please create work pattern & assign it to created locations.");
                    exit();
                }

                $this->locationWrkPtn[$locationId] = $locPattern->workPatternId;
            }

            $insertedEmployeeAndUser = $this->insertEmployeesUsersAndJobs();

            if (empty($insertedJobTitles) || empty($insertedLocations)) {
                print("Locations and Job Titles already inserted \n");
            }

            if ($insertedEmployeeAndUser && !empty($insertedJobTitles) || !empty($insertedLocations)) {
                print("Data seeded successfully \n");
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
        }
    }
}
