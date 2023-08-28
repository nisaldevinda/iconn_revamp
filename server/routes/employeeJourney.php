<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to employee journey controller.
 */
$router->group(['prefix' => 'api/employee-journey', 'middleware' => ['jwt']], function () use ($router) {
    $router->post('/{employeeId}/promotions', "EmployeeJourneyController@createNewPromotion");
    $router->post('/{employeeId}/confirmation-contracts', "EmployeeJourneyController@contractRenewal");
    $router->post('/{employeeId}/transfers', "EmployeeJourneyController@createNewTransfer");
    $router->post('/{employeeId}/resignations', "EmployeeJourneyController@createResignation");
    $router->put('/{employeeId}/update-current-job', "EmployeeJourneyController@updateCurrentJob");
    $router->get('/{employeeId}/attachment/{fileId}', "EmployeeJourneyController@getAttachment");
    $router->put('/{employeeId}/upcoming-milestone/{id}', "EmployeeJourneyController@reupdateUpcomingEmployeeJourneyMilestone");
    $router->delete('/{employeeId}/upcoming-milestone/{id}', "EmployeeJourneyController@rollbackUpcomingEmployeeJourneyMilestone");
    $router->post('/send-resignation-request', "EmployeeJourneyController@sendResignationRequest");
    $router->get('/resignation-attachment/{fileId}', "EmployeeJourneyController@getResignationAttachment");
});

$router->group(['prefix' => 'api/employee-journey/rehire-process', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/rejoin', "EmployeeJourneyController@getRejoinEligibleList");
    $router->get('/reactive', "EmployeeJourneyController@getReactiveEligibleList");
    $router->post('/rejoin/{employeeId}', "EmployeeJourneyController@rejoinEmployee");
    $router->post('/reactive/{employeeId}', "EmployeeJourneyController@reactiveEmployee");
});
