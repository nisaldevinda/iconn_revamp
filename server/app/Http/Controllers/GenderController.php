<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GenderService;

/*
    Name: GenderController
    Purpose: Performs request handling tasks related to the Gender model.
    Description: API requests related to the Gender model are directed to this controller.
    Module Creator: Chalaka
*/

class GenderController extends Controller
{
    protected $genderService;

    /**
     * GenderController constructor.
     *
     * @param GenderService $genderService
     */
    public function __construct(GenderService $genderService)
    {
        $this->genderService  = $genderService;
    }


    /*
        Creates a new Gender.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->genderService->createGender($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all Genders
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
        ];
        $result = $this->genderService->getAllGenders($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single Gender based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->genderService->getGender($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single Gender based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->genderService->getGenderByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single Gender is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->genderService->updateGender($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a Gender
    */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->genderService->softDeleteGender($id);
        return $this->jsonResponse($result);
    }
}
