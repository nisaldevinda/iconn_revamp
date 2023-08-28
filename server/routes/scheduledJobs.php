<?php

$router->group(['prefix' => 'api', 'middleware' => ['jwt']], function () use ($router) {
    $router->get('/scheduled-jobs-logs-history', "ScheduledJobLogsController@getScheduledJobsLogsHistory");
});
