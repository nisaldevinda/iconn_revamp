<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProRateFormulaService;

/*
    Name: ProRateFormulaController
    Purpose: Performs request handling tasks related to the Pro Rate Formula model.
    Description: API requests related to pro rate formula model are directed to this controller.
    Module Creator: Tharindu Darshana
*/

class ProRateFormulaController extends Controller
{
    protected $proRateFormulaService;

    /**
     * ProRateFormulaController constructor.
     *
     * @param ProRateFormulaService $proRateFormulaService
     */
    public function __construct(ProRateFormulaService $proRateFormulaService)
    {
        $this->proRateFormulaService  = $proRateFormulaService;
    }


    /*
        Sends a List of pro rate formula list.
    */
    public function getProRateFormulaList(Request $request)
    {
        $permission = $this->grantPermission('leave-type-config');

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
        $result = $this->proRateFormulaService->getProRateFormulaList($permittedFields, $options);

        return $result;
    }

}
