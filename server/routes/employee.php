<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to employee controller.
 */
//'middleware' => ['jwt']
$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/admin/employees-birthdays', "EmployeeController@getAdminUpcomingBirthDays");
    $router->get('/manager/employees-birthdays', "EmployeeController@getManagerUpcomingBirthDays");
    $router->get('/employee/employees-birthdays', "EmployeeController@getEmployeeUpcomingBirthDays");
    $router->get('/get-employee-employee-number', "EmployeeController@getEmployeeByEmployeeNumber");

    $router->get('/admin/employees-hired', "EmployeeController@getAdminUpcomingAnniversaryDays");
    $router->get('/manager/employees-hired', "EmployeeController@getManagerUpcomingAnniversaryDays");
    $router->get('/employee/employees-hired', "EmployeeController@getEmployeeUpcomingAnniversaryDays");

    $router->get('/employee-current/{id}', "EmployeeController@getCurrentJob");
    $router->get('/employee-profile-data-diff', "EmployeeController@getProfileUpdateDataDiff");

    $router->get('/employees-auto-id', "EmployeeController@getEmployeeId"); // route which will update the employee id in the db as well
    $router->get('/employees-current-auto-id', "EmployeeController@getCurrentEmployeeId"); // route to get employee id for form building purpose
    $router->get('/employee-number-format', "EmployeeController@getEmployeeNumberFormat");

    $router->get('/managers', "EmployeeController@getAllManagers");
    $router->get('/workflow-permitted-managers', "EmployeeController@getAllWorkflowPermittedManagers");
    $router->get('/employee-chart', "EmployeeController@getEmployeeOrgChart");

    //get user wise employees
    $router->get('/user-wise-employees', "EmployeeController@getUserWiseEmployees");

    //get employees list for master data department model
    $router->get('/check-is-shift-allocated/{id}',"EmployeeController@checkIsShiftAllocated");
    $router->get('/employees-list',"EmployeeController@getEmployeeList");
    $router->get('/all-employees-list',"EmployeeController@getAllEmployeeList");
    $router->get('/employees-list-by-entity-id',"EmployeeController@getEmployeeListByEntityId");
    $router->get('/employees-filtered',"EmployeeController@getEmployeesByKeyword");
    
    //get the list of all managers in the company
    $router->get('/get-subordinates-list/{id}',"EmployeeController@getSubordinatesForSelectedManager");
    //get employee list for selected Department and location
    $router->get('/get-emp-list-for-department-and-location',"EmployeeController@getEmployeesForDepartmentAndLocation");
     //get employee list for selected location
    $router->get('/get-emp-list-for-location/{id}',"EmployeeController@getEmployeesForLocation");
    $router->get('/get-emp-list-for-covering-person',"EmployeeController@getEmployeeListForCoveringPerson");
    $router->get('/get-emp-list-for-claim-allocation',"EmployeeController@getEmployeeListForClaimAllocation");
    
    //get employee list for selected Department
    $router->get('/get-emp-list-for-department/{id}',"EmployeeController@getEmployeesForDepartment");

    $router->get('/employee-side-card-details/{id}',"EmployeeController@getEmployeeSideCardDetails");
    $router->get('/get-unassigned-employees-list', "EmployeeController@getUnassignedEmployees");
    $router->get('/get-employees-root-nodes', "EmployeeController@getRootEmployees");

    $router->get('/get-emp-profile-pic/{id}', "EmployeeController@getEmpProfilePicture");
    $router->get('/next-employee-number', "EmployeeController@getNextEmployeeNumber");
    $router->post('/add-employee-number-config', "EmployeeController@addEmployeeNumberConfig");

});

// employees [by admin role]
$router->group(['prefix' => 'api/employees', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/', "EmployeeController@getAllEmployees");
    $router->get('/{id}', "EmployeeController@getEmployee");
    $router->post('/', "EmployeeController@createEmployee");
    $router->put('/{id}', "EmployeeController@updateEmployee");
    $router->delete('/{id}', "EmployeeController@deleteEmployee");

    // handle employee profile picture
    $router->get('/{id}/profilePicture', "EmployeeController@getEmployeeProfilePicture");
    $router->post('/{id}/profilePicture', "EmployeeController@storeEmployeeProfilePicture");
    $router->delete('/{id}/profilePicture', "EmployeeController@removeEmployeeProfilePicture");

    // handle multi-record fields cruds of employee
    $router->post('/{id}/{multirecordAttribute}', "EmployeeController@createEmployeeMultiRecord");
    $router->put('/{id}/{multirecordAttribute}/{multirecordId}', "EmployeeController@updateEmployeeMultiRecord");
    $router->delete('/{id}/{multirecordAttribute}/{multirecordId}', "EmployeeController@deleteEmployeeMultiRecord");

    // get employee field access permissions
    $router->get('/{id}/permission', "EmployeeController@getEmployeeFieldAccessPermission");

    $router->put('/{id}/change-active-status', "EmployeeController@changeEmployeeActiveStatus");

    $router->post('/{employeeId}/document-templates/{templateId}/download-docx', "DocumentTemplateController@downloadEmployeeDocumentAsDocx");
    $router->post('/{employeeId}/document-templates/{templateId}/download-pdf', "DocumentTemplateController@downloadEmployeeDocumentAsPdf");
    $router->get('/{employeeId}/document-templates/{templateId}', "DocumentTemplateController@getEmployeeDocument");
});

// my profile [by employee role]
$router->group(['prefix' => 'api/myProfile', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/', "EmployeeController@getMyProfile");
    $router->get('/view', "EmployeeController@getMyProfileView");
    $router->put('/update', "EmployeeController@updateMyProfile");

    // handle my profile picture
    $router->get('/{id}/profilePicture', "EmployeeController@getMyProfilePicture");
    $router->post('/{id}/profilePicture', "EmployeeController@storeMyProfilePicture");
    $router->delete('/{id}/profilePicture', "EmployeeController@removeMyProfilePicture");

    // handle multi-record fields cruds of my profile
    $router->post('/{multirecordAttribute}', "EmployeeController@createMyProfileMultiRecord");
    $router->put('/{multirecordAttribute}/{multirecordId}', "EmployeeController@updateMyProfileMultiRecord");
    $router->delete('/{multirecordAttribute}/{multirecordId}', "EmployeeController@deleteMyProfileMultiRecord");
    $router->get('/{multirecordAttribute}/{multirecordId}', "EmployeeController@getMyProfileMultiRecord");

    $router->get('/relational-dataset', "EmployeeController@getMyProfileRelationalData");
});


