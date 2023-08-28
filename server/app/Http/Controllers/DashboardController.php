<?php

namespace App\Http\Controllers;

use App\Library\Session;
use Illuminate\Http\Request;
use App\Services\DashboardService;

/*
    Name: DashboardController
    Purpose: Performs request handling tasks related to the Dashboard model.
    Description: API requests related to the Dashboard model are directed to this controller.
    Module Creator: Manjula
*/

class DashboardController extends Controller
{
    protected $dashboardService;

    protected $session;

    /**
     * DashboardController constructor.
     *
     * @param DashboardService $dashboardService
     */
    public function __construct(DashboardService $dashboardService, Session $session)
    {
        $this->dashboardService  = $dashboardService;
        $this->session = $session;
    }

    /*
        The dashboard based on employee id.
    */
    public function getDashboard(Request $request)
    {
        $userId = $this->session->user->id;
        $result = $this->dashboardService->getDashboard($userId);
        return $this->jsonResponse($result);
    }

    /*
        The dashboard will updated based on employee id.
    */
    public function updateDashboard(Request $request)
    {
        $userId = $this->session->user->id;
        $result = $this->dashboardService->updateDashboard($userId, $request->all());
        return $this->jsonResponse($result);
    }
}
