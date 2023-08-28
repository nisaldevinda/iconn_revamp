<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WorkShiftPayCofigurationService;

/*
    Name: PayTypeController
    Purpose: Performs request handling tasks related to the Work Calendar Day Type model.
    Description: API requests related to the Work Calendar Day Type Controller model are directed to this controller.
    Module Creator: Tharindu Darshana
*/

class WorkShiftPayCofigurationController extends Controller
{
    protected $workShiftPayCofigurationService;

    /**
     * PayTypeController constructor.
     *
     * @param WorkShiftPayCofigurationService $payTypeService
     */
    public function __construct(WorkShiftPayCofigurationService $workShiftPayCofigurationService)
    {
        $this->workShiftPayCofigurationService  = $workShiftPayCofigurationService;
    }


    /*
        Set pay Configuration.
    */
    public function setPayConfigurations($id, Request $request)
    {
        $permission = $this->grantPermission('work-shifts-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $calendarDateTypes = $this->workShiftPayCofigurationService->setPayConfigurations($id, $request->all());
        return $calendarDateTypes;
    }

    /*
        Set pay Configuration.
    */
    public function getPayConfiguration($id)
    {
        $permission = $this->grantPermission('work-shifts-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $calendarDateTypes = $this->workShiftPayCofigurationService->getPayConfiguration($id);
        return $calendarDateTypes;
    }

    /*
        get time base pay configurations state.
    */
    public function getTimeBasePayConfigState()
    {
        $permission = $this->grantPermission('work-shifts-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $calendarDateTypes = $this->workShiftPayCofigurationService->getTimeBasePayConfigState();
        return $calendarDateTypes;
    }


}
