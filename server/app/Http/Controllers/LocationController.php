<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LocationService;

/*
    Name: LocationController
    Purpose: Performs request handling tasks related to the Location model.
    Description: API requests related to the location model are directed to this controller.
    Module Creator: Hashan
*/

class LocationController extends Controller
{
    protected $locationService;

    /**
     * LocationController constructor.
     *
     * @param LocationService $locationService
     */
    public function __construct(LocationService $locationService)
    {
        $this->locationService  = $locationService;
    }

    /**
     * Retrives all locations
     */
    public function getAllLocations(Request $request)
    {
        $permission = $this->grantPermission('master-data-read');

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
            "searchFields" => $request->query('search_fields', ['name']),
        ];
        $result = $this->locationService->getAllLocations($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single location based on location_id.
    */
    public function getLocation($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->locationService->getLocation($id);
        return $this->jsonResponse($result);
    }


    /*
        Creates a new location.
    */
    public function createLocation(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->locationService->createLocation($request->all());
        return $this->jsonResponse($result);
    }

    /*
        A single location is updated.
    */
    public function updateLocation($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->locationService->updateLocation($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        A single location is deleted.
    */
    public function deleteLocation($id)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->locationService->deleteLocation($id);
        return $this->jsonResponse($result);
    }
    /*
      get Location by selected countryId
    */
    public function getLocationByCountryId(Request $request) 
    {
        $countryData = [
            "workPatternId" => $request->query("id",null),
            "countryId" => $request->query('countryId',null)
        ];
        $result = $this->locationService->getLocationByCountryId($countryData);
        return $this->jsonResponse( $result);
    }
    /*
      get Locations according to the admin
    */
    public function getAdminUserAccessLocations(Request $request) 
    {
        $permission = $this->grantPermission('admin-leave-request-access');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $sorter = $request->query("sorter",null);

        $result = $this->locationService->getAdminUserAccessLocations($sorter);
        return $this->jsonResponse( $result);
    }

}
