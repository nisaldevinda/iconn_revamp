<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Here you can register all the routes related to the bulkUpload controller.
 */

$router->group(['prefix' => 'api/bulk-upload/', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('download-template', "BulkUploadController@downloadTemplate");
    $router->get('download-leave-template', "BulkUploadController@downloadLeaveTemplate");
    $router->post('upload-template', "BulkUploadController@uploadTemplate");
    $router->post('upload-leave-template', "BulkUploadController@uploadLeaveTemplate");
    $router->post('save-validated-leave-entitlement-data', "BulkUploadController@saveValidatedUplodData");
    $router->get('upload-history', "BulkUploadController@getBulkUploadedHistory");
    $router->get('upload-history/{id}/file-object', "BulkUploadController@getFileObject");
    $router->get('salary-increment/template', "BulkUploadController@downloadSalaryIncrementTemplate");
    $router->post('salary-increment/upload', "BulkUploadController@uploadSalaryIncrementSheet");
    $router->post('salary-increment/finish', "BulkUploadController@completeSalaryIncrementSheet");
    $router->get('salary-increment/history', "BulkUploadController@getSalaryIncrementUploadHistory");
    $router->delete('salary-increment/rollback/{id}', "BulkUploadController@rollbackSalaryIncrementUpload");
    $router->get('employee-promotion/support', "BulkUploadController@getEmployeePromotionSupportData");
    $router->get('employee-promotion/template', "BulkUploadController@downloadEmployeePromotionTemplate");
    $router->post('employee-promotion/upload', "BulkUploadController@uploadEmployeePromotionSheet");
    $router->post('employee-promotion/finish', "BulkUploadController@completeEmployeePromotionSheet");
    $router->get('employee-promotion/history', "BulkUploadController@getEmployeePromotionUploadHistory");
    $router->delete('employee-promotion/rollback/{id}', "BulkUploadController@rollbackEmployeePromotionUpload");
    $router->get('employee-transfer/support', "BulkUploadController@getEmployeeTransferSupportData");
    $router->get('employee-transfer/template', "BulkUploadController@downloadEmployeeTransferTemplate");
    $router->post('employee-transfer/upload', "BulkUploadController@uploadEmployeeTransferSheet");
    $router->post('employee-transfer/finish', "BulkUploadController@completeEmployeeTransferSheet");
    $router->get('employee-transfer/history', "BulkUploadController@getEmployeeTransferUploadHistory");
    $router->delete('employee-transfer/rollback/{id}', "BulkUploadController@rollbackEmployeeTransferUpload");
});
