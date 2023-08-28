<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can documentmanagerfolder all the routes related to documentManager controller.
 */

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/documentmanager/folder-hierarchy', "DocumentManagerController@getFolderHierarchy");
    $router->get('/documentmanager/files', "DocumentManagerController@getFileList");
    $router->post('/documentmanager/files', "DocumentManagerController@uploadFile");
    $router->get('/documentmanager/files/{id}', "DocumentManagerController@getFile");
    $router->delete('/documentmanager/files/{id}', "DocumentManagerController@deleteFile");
    $router->post('/documentmanager/add-folder', "DocumentManagerController@addFolder");
    $router->put('/documentmanager/update-files/{id}', "DocumentManagerController@updateDocument");
    $router->put('/documentmanager/acknowledge-documnents/{id}', "DocumentManagerController@acknowledgeDocument");
    $router->get('/documentmanager/reports',"DocumentManagerController@documentManagerAcknowledgedReports");
    $router->get('/document-templates/employee-folder-files' ,"DocumentManagerController@getFilesInEmployeeFolders");
   
    $router->get('document-manager/acknowledge-count' ,"DocumentManagerController@getAcknowledgeCount");
    //by employee role
    $router->get('/documentmanager/my-folder-hierarchy', "DocumentManagerController@getMyFolderHierarchy");
    $router->get('/documentmanager/my-files', "DocumentManagerController@getMyFileList");
    $router->get('/documentmanager/my-files/{id}', "DocumentManagerController@getMyFile");
});
/* Need to include JWT
 */
$router->group(['prefix' => 'api'], function () use ($router) {
    $router->get('documentmanager/files-view/{id}',"DocumentManagerController@viewDocument");
});
