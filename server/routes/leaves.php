<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to leaves.
 */

//manange leave type related routes
$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/leave-types',  "LeaveTypeController@getAllLeaveTypes");
    $router->get('/leave-types/{id}', "LeaveTypeController@getLeaveType");
    $router->post('/leave-types', "LeaveTypeController@createLeaveType");
    $router->put('/leave-types/{id}', "LeaveTypeController@updateLeaveType");
    $router->delete('/leave-types/{id}', "LeaveTypeController@deleteLeaveType");
    $router->get('/leave-types-for-appliying',  "LeaveTypeController@getLeaveTypesForApplyLeave");
    $router->get('/leave-types-for-assign',  "LeaveTypeController@getLeaveTypesForAssignLeave");
    $router->get('/leave-types-for-appliying-for-employee',  "LeaveTypeController@getLeaveTypesForAdminApplyLeaveForEmployee");
    $router->get('/get-employee-entitlement-count',  "LeaveTypeController@getEmployeeEntitlementCount");
    $router->get('/leave-types-working-days',  "LeaveTypeController@getLeaveTypesWorkingDays");


    
    
});

//manange leave related routes
$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/leaves',  "LeaveRequestController@getAllLeaves");
    $router->get('/leaves/{id}', "LeaveRequestController@getLeave");
    $router->post('/leaves', "LeaveRequestController@createLeave");
    $router->put('/leaves/{id}', "LeaveRequestController@updateLeave");
    $router->delete('/leaves/{id}', "LeaveRequestController@deleteLeave");
    $router->post('/assign-leave', "LeaveRequestController@assignLeave");
    $router->get('/leave-attachment-list',  "LeaveRequestController@getLeaveAttachments");
    $router->get('/leave-covering-requests',  "LeaveRequestController@getLeaveCoveringRequests");
    $router->get('/leaveRequest/managerData', "LeaveRequestController@getManagerLeaveRequestData");
    $router->put('/leaveRequest/cancel-covering-person-based-leave/{id}', "LeaveRequestController@cancelCoveringPersonBasedLeaveRequest");
    $router->put('/leaveRequest/cancel-admin-assign-leave/{id}', "LeaveRequestController@cancelAdminAssignLeaveRequest");
    $router->get('/leaveRequest/employeeData', "LeaveRequestController@getEmployeeLeaveRequestData");
    $router->get('/leaveRequest/adminData', "LeaveRequestController@getAdminLeaveRequestData");
    $router->get('/leaveRequest/employeeLeavesHistory', "LeaveRequestController@getEmployeeLeavesHistory");
    $router->get('/leaveRequest/adminLeavesHistory', "LeaveRequestController@getAdminLeavesHistory");
    $router->get('/leaveRequest/getRelatedComments/{id}', "LeaveRequestController@getLeaveRequestRelatedComments");
    $router->put('/leaveRequest/addComment/{id}', "LeaveRequestController@addLeaveRequestComment");
    $router->get('/leaveRequest/leaveEntitlementUsage', "LeaveRequestController@getLeaveEntitlementUsage");
    $router->get('/calculate-working-days-count-for-leave', "LeaveRequestController@calculateWorkingDaysCountForLeave");
    $router->get('/get-shift-data-for-leave-date', "LeaveRequestController@getShiftDataForLeaveDate");
    $router->post('/leaveRequest/export-manager-data', "LeaveRequestController@exportManagerLeaves");
    $router->post('/leaveRequest/export-admin-data', "LeaveRequestController@exportAdminLeaves");
    $router->get('/leave-type-list',"LeaveTypeController@getLeaveTypesList");
    $router->get('/checkShortLeaveAccessabilityForCompany',"LeaveRequestController@checkShortLeaveAccessabilityForCompany");
    $router->get('/get-leave-data-details/{id}',"LeaveRequestController@getLeaveDateWiseDetails");
    $router->post('/team-leaves',"LeaveRequestController@applyTeamLeave");
    $router->put('/update-leave-covering-person-request', "LeaveRequestController@updateLeaveCoveringPersonRequest");
    $router->put('/cancel-leave-requests/{id}', "LeaveRequestController@cancelLeaveRequestDates");
    
    
});


//manange short-leave related routes
$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/short-leaves',  "ShortLeaveRequestController@getAllShortLeaves");
    $router->get('/short-leaves/{id}', "ShortLeaveRequestController@getShortLeave");
    $router->post('/short-leaves', "ShortLeaveRequestController@createShortLeave");
    $router->put('/short-leaves/{id}', "ShortLeaveRequestController@updateShortLeave");
    $router->delete('/short-leaves/{id}', "ShortLeaveRequestController@deleteShortLeave");
    $router->post('/assign-short-leave', "ShortLeaveRequestController@assignShortLeave");
    $router->put('/leaveRequest/cancel-admin-assign-short-leave/{id}', "ShortLeaveRequestController@cancelAdminAssignShortLeaveRequest");
    $router->get('/short-leave-attachment-list',  "ShortLeaveRequestController@getShortLeaveAttachments");
    $router->get('/short-leave/employeeShortLeaves', "ShortLeaveRequestController@getEmployeeShortLeavesHistoryData");
    $router->get('/short-leave/adminShortLeaves', "ShortLeaveRequestController@getAdminShortLeavesHistoryData");
    $router->get('/calculate-working-days-count-for-short-leave', "ShortLeaveRequestController@getShortLeaveDateIsWorkingDay");
    $router->post('/team-short-leaves',"ShortLeaveRequestController@applyTeamShortLeave");
    $router->put('/cancel-short-leave-requests/{id}', "ShortLeaveRequestController@cancelShortLeaveRequest");
});