<?php

require_once __DIR__ . '/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));
/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades(true, [
    'Illuminate\Support\Facades\Mail' => 'Mail',
    App\Library\Facades\Store::class => 'Store'
]);
$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
|
| Now we will register the "app" configuration file. If the file exists in
| your configuration directory it will be loaded; otherwise, we'll load
| the default version. You may register other files below as needed.
|
*/

$app->configure('app');
$app->configure('cookie');
$app->configure('permission');
$app->configure('logging');
$app->configure('mail');
$app->configure('queue');
$app->configure('jwt');
$app->configure('redis');
$app->configure('dompdf');
$app->configure('database'); // added in order to support group by Query Statements
$app->configure('reportFilterDefinitions');
$app->configure('fileStorage');
$app->configure('tenancy');
$app->configure('models');
$app->configure('workflowToken');
$app->configure('auth');


/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

// $app->middleware([
//     App\Http\Middleware\ExampleMiddleware::class
// ]);

$app->middleware([
    App\Http\Middleware\CorsMiddleware::class
]);

$app->routeMiddleware([
    // 'auth' => App\Http\Middleware\Authenticate::class,
    'client' => \Laravel\Passport\Http\Middleware\CheckClientCredentials::class,
    'scope' => \Laravel\Passport\Http\Middleware\CheckClientCredentialsForAnyScope::class,
]);

$app->routeMiddleware([
    'jwt' => App\Http\Middleware\JWTMiddleware::class,
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
$app->register(App\Providers\StoreServiceProvider::class);
$app->register(App\Providers\RedisServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);
$app->register(Illuminate\Mail\MailServiceProvider::class);
$app->register(Illuminate\Redis\RedisServiceProvider::class);
$app->register(\Barryvdh\DomPDF\ServiceProvider::class);
$app->register(App\Providers\SessionServiceProvider::class);
$app->register(App\Providers\FileStoreServiceProvider::class);
// $app->register(App\Providers\ContextServiceProvider::class);
// $app->register(App\Providers\PermissionServiceProvider::class);
// $app->register(App\Providers\CorsServiceProvider::class);
$app->register(Maatwebsite\Excel\ExcelServiceProvider::class);
$app->register(Laravel\Passport\PassportServiceProvider::class);
$app->register(Dusterio\LumenPassport\PassportServiceProvider::class);
Dusterio\LumenPassport\LumenPassport::routes($app->router, ['prefix' => 'api/v1/oauth']);
$app->register(\Thedevsaddam\LumenRouteList\LumenRouteListServiceProvider::class);
$app->register(App\Providers\ActiveDirectoryServiceProvider::class);
$app->register(App\Providers\AzureUserServiceProvider::class);

$app->alias('mail.manager', Illuminate\Mail\MailManager::class);
$app->alias('mail.manager', Illuminate\Contracts\Mail\Factory::class);
$app->alias('mailer', Illuminate\Mail\Mailer::class);
$app->alias('mailer', Illuminate\Contracts\Mail\Mailer::class);
$app->alias('mailer', Illuminate\Contracts\Mail\MailQueue::class);

if (!class_exists('Redis')) {
    class_alias('Illuminate\Support\Facades\Redis', 'Redis');
}

if (!class_exists('Excel')) {
    class_alias('Maatwebsite\Excel\Facades\Excel', 'Excel');
}

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__ . '/../routes/web.php';
    require __DIR__ . '/../routes/users.php';
    require __DIR__ . '/../routes/auth.php';
    require __DIR__ . '/../routes/models.php';
    require __DIR__ . '/../routes/countryAndState.php';
    require __DIR__ . '/../routes/dashboard.php';
    require __DIR__ . '/../routes/division.php';
    require __DIR__ . '/../routes/department.php';
    require __DIR__ . '/../routes/location.php';
    require __DIR__ . '/../routes/qualificationLevels.php';
    require __DIR__ . '/../routes/qualifications.php';
    require __DIR__ . '/../routes/qualificationInstitutions.php';
    require __DIR__ . '/../routes/jobTitles.php';
    require __DIR__ . '/../routes/maritalStatus.php';
    require __DIR__ . '/../routes/nationalities.php';
    require __DIR__ . '/../routes/relationships.php';
    require __DIR__ . '/../routes/religions.php';
    require __DIR__ . '/../routes/genders.php';
    require __DIR__ . '/../routes/terminationReasons.php';
    require __DIR__ . '/../routes/auditLogs.php';
    require __DIR__ . '/../routes/auditTrail.php';
    require __DIR__ . '/../routes/employmentStatus.php';
    require __DIR__ . '/../routes/race.php';
    require __DIR__ . '/../routes/companies.php';
    require __DIR__ . '/../routes/salaryComponents.php';
    require __DIR__ . '/../routes/payGrades.php';
    require __DIR__ . '/../routes/userRoles.php';
    require __DIR__ . '/../routes/employee.php';
    require __DIR__ . '/../routes/myTeams.php';
    require __DIR__ . '/../routes/documentManager.php';
    require __DIR__ . '/../routes/attendance.php';
    require __DIR__ . '/../routes/attendanceSheet.php';
    require __DIR__ . '/../routes/notices.php';
    require __DIR__ . '/../routes/reportData.php';
    require __DIR__ . '/../routes/competencyType.php';
    require __DIR__ . '/../routes/competency.php';
    require __DIR__ . '/../routes/workflowActions.php';
    require __DIR__ . '/../routes/workflowStates.php';
    require __DIR__ . '/../routes/workflowContext.php';
    require __DIR__ . '/../routes/workflowDefine.php';
    require __DIR__ . '/../routes/workflowPermission.php';
    require __DIR__ . '/../routes/workflowStateTransition.php';
    require __DIR__ . '/../routes/workflow.php';
    require __DIR__ . '/../routes/documentTemplate.php';
    require __DIR__ . '/../routes/emailTemplate.php';
    require __DIR__ . '/../routes/workSchedule.php';
    require __DIR__ . '/../routes/workPattern.php';
    require __DIR__ . '/../routes/workCalendar.php';
    require __DIR__ . '/../routes/bulkUpload.php';
    require __DIR__ . '/../routes/leaves.php';
    require __DIR__ . '/../routes/leaveEntitlement.php';
    require __DIR__ . '/../routes/manualProcess.php';
    require __DIR__ . '/../routes/leaveTypeConfig.php';
    require __DIR__ . '/../routes/scheduledJobs.php';
    require __DIR__ . '/../routes/payRoll.php';
    require __DIR__ . '/../routes/dynamicModel.php';
    require __DIR__ . '/../routes/dynamicForm.php';
    require __DIR__ . '/../routes/testAuth.php';
    require __DIR__ . '/../routes/workShift.php';
    require __DIR__ . '/../routes/payType.php';
    require __DIR__ . '/../routes/proRateFormula.php';
    require __DIR__ . '/../routes/workflowEmployeeGroup.php';
    require __DIR__ . '/../routes/tenant.php';
    require __DIR__ . '/../routes/importAzureUsers.php';
    require __DIR__ . '/../routes/jobCategories.php';
    require __DIR__ . '/../routes/noticePeriodConfigs.php';
    require __DIR__ . '/../routes/shiftAssign.php';
    require __DIR__ . '/../routes/noticeCategory.php';
    require __DIR__ . '/../routes/scheme.php';
    require __DIR__ . '/../routes/employeeJourney.php';
    require __DIR__ . '/../routes/confirmationReason.php';
    require __DIR__ . '/../routes/promotionType.php';
    require __DIR__ . '/../routes/resignationType.php';
    require __DIR__ . '/../routes/transferType.php';
    require __DIR__ . '/../routes/formTemplates.php';
    require __DIR__ . '/../routes/WorkflowApproverPool.php';
    require __DIR__ . '/../routes/selfServiceLock.php';
    require __DIR__ . '/../routes/resignationProcess.php';
    require __DIR__ . '/../routes/confirmationProcess.php';
    require __DIR__ . '/../routes/employeeNumberConfiguration.php';
    require __DIR__ . '/../routes/financialYear.php';
    require __DIR__ . '/../routes/expenseManagement.php';
    require __DIR__ . '/../routes/payments.php';
});

return $app;
