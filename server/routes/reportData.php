<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */
$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/report-data', "ReportDataController@store");
    $router->get('/report-data/{id}', "ReportDataController@showById");
    $router->get('/report-data/admin/{id}/get-report', "ReportDataController@getAdminReport");
    $router->get('/report-data/manager/{id}/get-report', "ReportDataController@getManagerReport");
    $router->get('/report-data/employee/{id}/get-report', "ReportDataController@getEmployeeReport");
    $router->get('/report-data/{id}/query-report-dynamically', "ReportDataController@queryReportWithDynamicFilters");
    $router->get('/report-data-names', "ReportDataController@getReportNamesWithId");
    $router->put('/report-data/{id}', "ReportDataController@update");
    $router->delete('/report-data/{id}', "ReportDataController@delete");
    $router->get('/report-filter-definitions', "ReportDataController@getFilterDefinitions");
    $router->get('/report-data/admin/{id}/get-chart', "ReportDataController@getAdminChart");
    $router->get('/report-data/manager/{id}/get-chart', "ReportDataController@getManagerChart");
    $router->get('/report-data/employee/{id}/get-chart', "ReportDataController@getEmployeeChart");
    $router->get('/report-data/admin/get-all', "ReportDataController@getAdminReportList");
    $router->get('/report-data/manager/get-all', "ReportDataController@getManagerReportList");
    $router->get('/report-data/employee/get-all', "ReportDataController@getEmployeeReportList");
    $router->get('/report/admin/{id}/download-report-by-format', "ReportDataController@downloadAdminReportByFormat");
    $router->get('/report/manager/{id}/download-report-by-format', "ReportDataController@downloadManagerReportByFormat");
    $router->get('/report/employee/{id}/download-report-by-format', "ReportDataController@downloadEmployeeReportByFormat");




});
