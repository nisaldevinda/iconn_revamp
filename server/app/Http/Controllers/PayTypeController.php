<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PayTypeService;

/*
    Name: PayTypeController
    Purpose: Performs request handling tasks related to the Work Calendar Day Type model.
    Description: API requests related to the Work Calendar Day Type Controller model are directed to this controller.
    Module Creator: Tharindu Darshana
*/

class PayTypeController extends Controller
{
    protected $payTypeService;

    /**
     * PayTypeController constructor.
     *
     * @param PayTypeService $payTypeService
     */
    public function __construct(PayTypeService $payTypeService)
    {
        $this->payTypeService  = $payTypeService;
    }


    /*
        Creates a new Pay Type.
    */
    public function createPayType(Request $request)
    {
        $permission = $this->grantPermission('pay-type-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->payTypeService->createPayType($data);
        return $this->jsonResponse($result);
    }

  

    /*
        Sends a List of date types.
    */
    public function getPayTypeList(Request $request)
    {
        $permission = $this->grantPermission('pay-type-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $permittedFields = ["*"];
        $options = [
            "sorter" => $request->query('sorter', null),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', null),
        ];
        $result = $this->payTypeService->getPayTypeList($permittedFields, $options);

        return $result;
    }

    /*
       Get Ot pay type list
    */
    public function getOTPayTypeList(Request $request)
    {
        // $permission = $this->grantPermission('pay-type-read-write');

        // if (!$permission->check()) {
        //     return $this->forbiddenJsonResponse();
        // }

        $permittedFields = ["*"];
        $options = [
            "sorter" => $request->query('sorter', null),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('keyword', null),
            "searchFields" => $request->query('search_fields', null),
        ];
        $result = $this->payTypeService->getOTPayTypeList($permittedFields, $options);

        return $result;
    }

    /*
        Edit calendar day type.
    */
    public function updatePayType($id, Request $request)
    {
        $permission = $this->grantPermission('pay-type-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $calendarDateTypes = $this->payTypeService->updatePayTypeData($id, $request->all());
        return $calendarDateTypes;
    }


    /*
        Delete Calendar day type
    */
    public function deletePayType($id, Request $request)
    {

        $permission = $this->grantPermission('pay-type-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $calendarDateTypes = $this->payTypeService->deletePayType($id);
        return $calendarDateTypes;
    }
}
