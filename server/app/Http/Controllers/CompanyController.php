<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CompanyService;

/*
    Name: CompanyController
    Purpose: Performs request handling tasks related to the Company model.
    Description: API requests related to the company model are directed to this controller.
    Module Creator: Yohan
*/

class CompanyController extends Controller
{
    protected $companyService;

    /**
     * CompanyController constructor.
     *
     * @param CompanyService $companyService
     */
    public function __construct(CompanyService $companyService)
    {
        $this->companyService  = $companyService;
    }

    /*
        Retrives a single company based on company_id.
    */
    public function getCompany()
    {
        $permission = $this->grantPermission('company-info-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->companyService->getCompany();
        return $this->jsonResponse($result);
    }

    /*
        A single company is updated.
    */
    public function updateCompany($id, Request $request)
    {
        $permission = $this->grantPermission('company-info-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->companyService->updateCompany($id, $request->all());
        return $this->jsonResponse($result);
    }

    public function getImages(Request $request)
    {
        $imageType = $request->query('type', null);

        $result = $this->companyService->getImages($imageType);
        return $this->jsonResponse($result);
    }

    public function storeImages(Request $request)
    {
        $imageType = $request->query('type', 'icon');
        $permission = $this->grantPermission('company-info-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->companyService->storeImages($imageType, $request->all());
        return $this->jsonResponse($result);
    }

}
