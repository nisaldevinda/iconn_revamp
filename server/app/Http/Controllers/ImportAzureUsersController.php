<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ImportAzureUsersService;

/*
    Name: ImportAzureUsersController
    Purpose: Performs request handling tasks related to the ImportAzureUser model.
    Description: API requests related to the ImportAzureUser model are directed to this controller.
    Module Creator: Hashan
*/

class ImportAzureUsersController extends Controller
{
    protected $importAzureUsersService;

    /**
     * ImportAzureUsersController constructor.
     *
     * @param ImportAzureUsersService $importAzureUsersService
     */
    public function __construct(ImportAzureUsersService $importAzureUsersService)
    {
        $this->importAzureUsersService = $importAzureUsersService;
    }

    /*
        Creates a new ImportAzureUser.
    */
    public function setup(Request $request)
    {
        $permission = $this->grantPermission('azure-active-directory');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->importAzureUsersService->setup($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all ImportAzureUsers
     */
    public function getStatus(Request $request)
    {
        $permission = $this->grantPermission('azure-active-directory');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->importAzureUsersService->getStatus();
        return $this->jsonResponse($result);
    }

    /**
     * Retrives azure user mapping
     */
    public function getFieldMap()
    {
        $permission = $this->grantPermission('azure-active-directory');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->importAzureUsersService->getFieldMap();
        return $this->jsonResponse($result);
    }

    /**
     * Retrives azure user mapping
     */
    public function getConfig()
    {
        $permission = $this->grantPermission('azure-active-directory');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->importAzureUsersService->getConfig();
        return $this->jsonResponse($result);
    }

    /*
        Creates a new ImportAzureUser.
    */
    public function storeAuthConfig(Request $request)
    {
        $permission = $this->grantPermission('azure-active-directory');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->importAzureUsersService->storeAuthConfig($data);
        return $this->jsonResponse($result);
    }

    /*
        Creates a new ImportAzureUser.
    */
    public function storeUserProvisioningConfig(Request $request)
    {
        $permission = $this->grantPermission('azure-active-directory');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->importAzureUsersService->storeUserProvisioningConfig($data);
        return $this->jsonResponse($result);
    }
}
