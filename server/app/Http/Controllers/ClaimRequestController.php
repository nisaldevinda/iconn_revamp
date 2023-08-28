<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ClaimRequestService;

/*
    Name: ClaimRequestController
    Purpose: Performs request handling tasks related to the ClaimType model.
    Description: API requests related to the ClaimType model are directed to this controller.
    Module Creator: Tharindu Darshana
*/

class ClaimRequestController extends Controller
{
    protected $claimRequestService;

    /**
     * ClaimRequestController constructor.
     *
     * @param ClaimRequestService $claimRequestService
     */
    public function __construct(ClaimRequestService $claimRequestService)
    {
        $this->claimRequestService  = $claimRequestService;
    }

    
    /*
        Creates a new Financial Year
    */
    public function createEmployeeClaimRequest(Request $request)
    {
        $permission = $this->grantPermission('claim-request-employee-access');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result = $this->claimRequestService->createEmployeeClaimRequest($request->all());
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single leave type based on leave_type_id.
    */
    public function getClaimRequestReceiptDetails($id)
    {
        
        $result = $this->claimRequestService->getClaimRequestReceiptDetails($id);
        return $this->jsonResponse($result);
    }

     /*
        fetch resignation attachment.
    */
    public function getReceiptAttachment($id)
    {
    
        $result = $this->claimRequestService->getReceiptAttachment($id);
        return $this->jsonResponse($result);
    }    
}