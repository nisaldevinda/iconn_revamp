<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to attendance sheet controller.
 */

$router->group(['prefix' => 'api'], function () use ($router) {
        $router->group(['middleware' => ['jwt']], function () use ($router) {
                $router->get('/attendanceSheet/employees', "AttendanceSheetController@getEmployeesPerManager");
                $router->get('/attendanceSheet/managerData', "AttendanceSheetController@getManagerAttendanceSheetData");
                $router->get('/attendanceSheet/getAttendanceReportData', "AttendanceSheetController@getAttendanceReportsData");
                $router->get('/attendanceSheet/employeeData', "AttendanceSheetController@getEmployeeAttendanceSheetData");
                $router->get('/attendanceSheet/getPostOtRequestAttendance', "AttendanceSheetController@getPostOtRequestAttendanceSheetData");
                $router->get('/attendanceSheet/getAttendanceDetailsByPostOtRequestId/{id}', "AttendanceSheetController@getAttendanceDetailsByPostOtRequestId");
                $router->get('/attendanceSheet/getRelatedBreakes', "AttendanceSheetController@getAttendanceRelatedBreaks");
                $router->get('/attendanceSheet/checkOtAccessability', "AttendanceSheetController@checkOtAccessability");
                $router->get('/attendanceSheet/checkOtAccessabilityForCompany', "AttendanceSheetController@checkOtAccessabilityForCompany");
                $router->get('/attendanceSheet/summary', "AttendanceSheetController@getDailySummeryData");
                $router->get('/attendanceSheet/othersSummary', "AttendanceSheetController@getOthersDailySummeryData");
                $router->get('/attendanceSheet/adminData', "AttendanceSheetController@getAdminAttendanceSheetData");
                $router->get('/attendanceSheet/invalid-attendance', "AttendanceSheetController@getInvalidAttendanceSheetData");
                $router->post('/attendanceSheet/timeChange', "AttendanceSheetController@updateAttendanceTime");
                $router->post('/attendanceSheet/updateBreaks', "AttendanceSheetController@updateBreakRecordsAdmin");
                $router->get('/attendanceSheet/requestTimeData', "AttendanceSheetController@requestAttendanceTime");
                $router->post('/attendanceSheet/approveTime', "AttendanceSheetController@approveAttendanceTime");
                $router->post('/attendanceSheet/timeChangeAdmin', "AttendanceSheetController@updateAttendanceTimeAdmin");
                $router->post('/attendanceSheet/updateInvalidAttendance', "AttendanceSheetController@updateInvalidAttendances");
                $router->post('/attendanceSheet/createPostOtRequest', "AttendanceSheetController@createPostOtRequest");
                $router->get('/attendanceSheet/downloadManagerAttendanceView', "AttendanceSheetController@downloadManagerAttendanceView");
                $router->get('/attendanceSheet/downloadAdminAttendanceView', "AttendanceSheetController@downloadAdminAttendanceView");

        });
});
