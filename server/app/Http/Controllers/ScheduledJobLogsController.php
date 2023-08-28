<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ScheduledJobLogsService;


class ScheduledJobLogsController extends Controller
{
    protected $scheduledJobLogsService;
    /**
     * ScheduledJobLogsController constructor.
     *
     * @param NoticeService $ScheduledJobLogsController
     */

    public function __construct(ScheduledJobLogsService $scheduledJobLogsService)
    {
        $this->scheduledJobLogsService  = $scheduledJobLogsService;
    }

    /**
     * Retrieves all scheduled logs
     */
    public function getScheduledJobsLogsHistory(Request $request)
    {
        $permission = $this->grantPermission('scheduled-jobs-log');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->scheduledJobLogsService->getScheduledJobsLogsHistory($request->all());
        return $this->jsonResponse($result);
    }

   
}
