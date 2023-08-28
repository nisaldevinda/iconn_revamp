<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to user controller.
 */


$router->group(['prefix' => 'api/expense-management','middleware' => ['jwt']], function () use ($router) {
    //claim category
    $router->get('/claimCategories', "ClaimCategoryController@getAllClaimCategories");
    $router->post('/claimCategories', "ClaimCategoryController@createClaimCategory");
    $router->put('/claimCategories/{id}', "ClaimCategoryController@updateClaimCategory");
    $router->delete('/claimCategories/{id}', "ClaimCategoryController@deleteClaimCategory");

    //claim types
    $router->get('/claimTypes', "ClaimTypeController@getAllClaimTypes");
    $router->get('/claimTypes/{id}', "ClaimTypeController@getClaimType");
    $router->get('/get-allocation-enable-claim-types', "ClaimTypeController@getAllocationEnableClaimTypes");
    $router->get('/get-employee-eligible-claim-types', "ClaimTypeController@getEmployeeEligibleClaimTypes");
    $router->get('/get-employee-claim-alocation-list', "ClaimTypeController@getEmployeeClaimAllocationList");
    $router->post('/add-employee-claim-alocation', "ClaimTypeController@addEmployeeClaimAllocation");
    $router->post('/add-bulk-employee-claim-alocation', "ClaimTypeController@addBulkEmployeeClaimAllocation");
    $router->post('/update-employee-claim-allocations', "ClaimTypeController@updateEmployeeClaimAllocations");
    $router->post('/claimTypes', "ClaimTypeController@createClaimType");
    $router->put('/claimTypes/{id}', "ClaimTypeController@updateClaimType");
    $router->delete('/claimTypes/{id}', "ClaimTypeController@deleteClaimType");
    $router->delete('/claimAllocation/{id}', "ClaimTypeController@deleteEmployeeClaimAllocation");
    $router->get('/getClaimTypesByEntityId', "ClaimTypeController@getClaimTypesByEntityId");
    $router->get('/get-employee-claim-allocation-data', "ClaimTypeController@getEmployeeClaimAllocationData");

    $router->post('/create-employee-claim-request', "ClaimRequestController@CreateEmployeeClaimRequest");
    $router->get('/get-claim-request-receipt-details/{id}', "ClaimRequestController@getClaimRequestReceiptDetails");
    $router->get('/receipt-attachment/{id}', "ClaimRequestController@getReceiptAttachment");


    //claim packages
    $router->get('/claimPackages', "ClaimPackageController@getAllClaimPackages");
    $router->post('/claimPackages', "ClaimPackageController@createClaimPackage");
    $router->put('/claimPackages/{id}', "ClaimPackageController@updateClaimPackage");
    $router->delete('/claimPackages/{id}', "ClaimPackageController@deleteClaimPackage");


    
});