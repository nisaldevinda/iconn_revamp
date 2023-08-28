<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PayRollService;

/*
    Name: PayRollController
    Purpose: Performs request handling tasks related to the pay roll.
    Description: API requests related to the pay roll are directed to this controller.
    Module Creator: Tharindu Darshana
*/

class PayRollController extends Controller
{
    protected $payRoll;

    /**
     * RaceController constructor.
     *
     * @param RaceService $race
     */
    public function __construct(PayRollService $payRoll)
    {
        $this->payRoll  = $payRoll;
    }

    /*
        get Employee profile details for pay roll
    */
    public function getEmployeeProfilesForPayRoll(Request $request)
    {
        $options = [
            "pageNo" => $request->query('pageNo', null),
            "lastDataSyncTimeStamp" => $request->query('lastDataSyncTimeStamp', null),
        ];
        $result = $this->payRoll->getEmployeeProfilesForPayRoll($options);
        return $this->jsonResponse($result);
    }
    /*
        get Employee Attendance summery details for pay roll
    */
    public function getEmployeeAttendanceSummeryForPayRoll(Request $request)
    {
        $options = [
            "pageNo" => $request->query('pageNo', null),
            "from" => $request->query('from', null),
            "to" => $request->query('to', null),
        ];
        $result = $this->payRoll->getEmployeeAttendanceSummeryForPayRoll($options);
        return $this->jsonResponse($result);
    }


    /*
        get Employee Attendance summery details for pay roll
    */
    public function changeAttendanceRecordsStateForPayRoll(Request $request)
    {
        $result = $this->payRoll->changeAttendanceRecordsStateForPayRoll($request->all());
        return $this->jsonResponse($result);
    }

    public function uploadPayslips(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $result = $this->payRoll->uploadPayslips($data);
        return $this->jsonResponse($result);
    }

    public function getRecentPayslips()
    {
        $result = $this->payRoll->getRecentPayslips();
        return $this->jsonResponse($result);
    }

    public function getPayslip($id)
    {
        $result = $this->payRoll->getPayslip($id);
        return $this->jsonResponse($result);
    }
    
}
