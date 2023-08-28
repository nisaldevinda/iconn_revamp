<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to work calendar controller.
 */
$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {

    $router->post('/work-calender', "WorkCalendarController@store");
    $router->post('/work-calender/special-day', "WorkCalendarController@creatSpecialDate");
    $router->get('/work-calender/time-object', "WorkCalendarController@getTimeObject");
    $router->get('/work-calender/calendar-meta-data/', "WorkCalendarController@getCalendarMetaData");
    $router->get('/work-calender/calendar-list', "WorkCalendarController@getCalendarList");
    $router->get('/work-calender/calendar-date-types', "WorkCalendarController@getCalendarDateTypes");
    $router->get('/work-calender/calendar-summery', "WorkCalendarController@getCalendarSummery");
    $router->put('/work-calender/{id}/edit-calendar-name', "WorkCalendarController@editCalendarName");


    //work-calendar-day-type
    $router->post('/work-calendar-day-types', "WorkCalendarDayTypeController@createDayType");
    $router->get('/work-calendar-day-types', "WorkCalendarDayTypeController@getDayTypeList");
    $router->get('/get-all-base-day-types', "WorkCalendarDayTypeController@getAllBaseDayTypeList");
    $router->put('/work-calendar-day-types/{id}', "WorkCalendarDayTypeController@updateDayType");
    $router->delete('/work-calendar-day-types/{id}', "WorkCalendarDayTypeController@deleteDayType");
});
