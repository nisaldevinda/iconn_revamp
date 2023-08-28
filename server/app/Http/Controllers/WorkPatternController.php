<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WorkPatternService;

/*
    Name: WorkPatternController
    Purpose: Performs request handling tasks related to the Work Pattern model.
    Description: API requests related to the Work Pattern model are directed to this controller.
*/

class WorkPatternController extends Controller
{   

    protected $workPatternService;

    /**
     * WorkPatternController constructor.
     *
     * @param WorkPatternService $workPatternService
     */
    public function __construct(WorkPatternService $workPatternService)
    {
       
        $this->workPatternService  = $workPatternService;
    }

    /**
     * Create a new Work Pattern .
     */
    public function store(Request $request)
    {
        $permission = $this->grantPermission('work-pattern-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);

        $result =  $this->workPatternService->createWorkPattern($data);
        return $this->jsonResponse($result);
    }
    
    /**
     * Create duplicate Work pattern
     */
    public function createDuplicatePattern(Request $request)
    {
        $permission = $this->grantPermission('work-pattern-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);

        $result =  $this->workPatternService->createDuplicatePattern($data);
        return $this->jsonResponse($result);
    }
    
     /**
     * Get all Work Patterns
     */
    public function list(Request $request)
    {
        $permission = $this->grantPermission('work-pattern-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $permittedFields = ["id", "name", "description","createdAt"];
        $options = [
            "sorter" => $request->query('sort', null),
            "pageSize" => $request->query('pageSize', null),
            "current" => $request->query('current', null),
            "filter" => $request->query('filter', null),
            "keyword" => $request->query('searchText', null),
            "searchFields" => $request->query('search_fields', ['name']),
        ];

        $result =  $this->workPatternService->listWorkPatterns($permittedFields, $options);
        return $this->jsonResponse($result);
    }

    /**
     * Get single Work Pattern  by id
     */
    public function getById($id)
    {
        $permission = $this->grantPermission('work-pattern-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result =  $this->workPatternService->getWorkPattern($id);
        return $this->jsonResponse($result);
    }

    /**
     * Get single Work Pattern  by id
     */
    public function getWorkPaternRelatedEmployees($id)
    {
        $permission = $this->grantPermission('work-pattern-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        
        $result =  $this->workPatternService->getWorkPaternRelatedEmployees($id);
        return $this->jsonResponse($result);
    }

   



    /**
     * UpdateWork Pattern by id
     */
    public function update($id, Request $request)
    {
        $permission = $this->grantPermission('work-pattern-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result =  $this->workPatternService->updateWorkPattern($id, $request->all());
        return $this->jsonResponse($result);
    }

    /**
     * Delete Work Pattern  by id
     */
    public function delete($id, Request $request)
    {
        $permission = $this->grantPermission('work-pattern-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }
        $result =  $this->workPatternService->deleteWorkPattern($id);
        return $this->jsonResponse($result);
    }

    /**
     * 
     * Delete week in a work pattern
     */

    public function deleteWeek($id, Request $request) 
    {
        $permission = $this->grantPermission('work-pattern-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result =  $this->workPatternService->deleteWeek($id, $request->all());
        return $this->jsonResponse($result);
    }
    /**
     * 
     * Retrieves work patterns with name and id for work schedule
     */
    public function listAllWorkPatterns(Request $request)
    {
        $permission = $this->grantPermission('work-schedule-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $result =  $this->workPatternService->listAllWorkPatterns();
        return $this->jsonResponse($result);
    }

    /**
     * Assign Work Pattern to employee .
     */
    public function assignWorkPatterns(Request $request)
    {
        $permission = $this->grantPermission('work-pattern-assign-read-write');

        if (!$permission->check()) {
            return $this->forbiddenJsonResponse();
        }

        $data = json_decode($request->getContent(), true);

        $result =  $this->workPatternService->assignWorkPatterns($data);
        return $this->jsonResponse($result);
    }
}
