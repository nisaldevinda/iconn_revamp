<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RelationshipService;

/*
    Name: RelationshipController
    Purpose: Performs request handling tasks related to the Relationship model.
    Description: API requests related to the Relationship model are directed to this controller.
    Module Creator: Chalaka
*/

class RelationshipController extends Controller
{
    protected $relationshipService;

    /**
     * RelationshipController constructor.
     *
     * @param RelationshipService $relationshipService
     */
    public function __construct(RelationshipService $relationshipService)
    {
        $this->relationshipService  = $relationshipService;
    }


    /*
        Creates a new Relationship.
    */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        $result = $this->relationshipService->createRelationship($data);
        return $this->jsonResponse($result);
    }

    /**
     * Retrives all Relationships
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
        $result = $this->relationshipService->getAllRelationships($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single Relationship based on id.
    */
    public function showById($id)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->relationshipService->getRelationship($id);
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single Relationship based on keyword.
    */
    public function showByKeyword($keyword)
    {
        $permission = $this->grantPermission('master-data-read');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result = $this->relationshipService->getRelationshipByKeyword($keyword);
        return $this->jsonResponse($result);
    }
    

    /*
        A single Relationship is updated.
    */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->relationshipService->updateRelationship($id, $request->all());
        return $this->jsonResponse($result);
    }

    /*
        Delete a Relationship
    */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('master-data-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result = $this->relationshipService->softDeleteRelationship($id);
        return $this->jsonResponse($result);
    }
}
