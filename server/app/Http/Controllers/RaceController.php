<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RaceService;

/*
    Name: RaceController
    Purpose: Performs request handling tasks related to the Race model.
    Description: API requests related to the Race model are directed to this controller.
    Module Creator: Yohan
*/

class RaceController extends Controller
{
    protected $race;

    /**
     * RaceController constructor.
     *
     * @param RaceService $race
     */
    public function __construct(RaceService $race)
    {
        $this->race  = $race;
    }


    /*
        Creates a new Race.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->race->createRace($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all Race
     */
    public function index(Request $request)
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
        $result = $this->race->getAllRace($permittedFields, $options);
        return $this->jsonResponse($result);
      
    }

    /*
        Retrives a single Race based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->race->getRace($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single Race based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->race->getRaceByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single Race is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->race->updateRace($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a Race
    */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->race->softDeleteRace($id);
        return $this->jsonResponse($result);
    }
}
