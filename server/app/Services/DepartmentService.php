<?php

namespace App\Services;

use Log;
use Exception;
use App\Library\Store;
use App\Library\Util;
use Illuminate\Support\Facades\Lang;
use App\Library\ModelValidator;
use Illuminate\Support\Facades\DB;
use App\Traits\JsonModelReader;
use App\Traits\ConfigHelper;
use Carbon\Carbon;

/**
 * Name: DepartmentService
 * Purpose: Performs tasks related to the User Role model.
 * Description: User Role Service class is called by the DepartmentController where the requests related
 * to User Role Model (CRUD operations and others).
 * Module Creator: Hashan
 */
class DepartmentService extends BaseService
{
    use JsonModelReader;
    use ConfigHelper;

    private $store;
    private $departmentModel;
    private $employeeModel;
    private $jobModel;
    private $orgEntityModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->departmentModel =  $this->getModel('department', true);
        $this->employeeModel =  $this->getModel('employee', true);
        $this->jobModel =  $this->getModel('employeeJob', true);
        $this->orgEntityModel =  $this->getModel('orgEntity', true);
    }

    /**
     * Following function creates a user role. The user role details that are provided in the Request
     * are extracted and saved to the user role table in the database. user_role_id is auto genarated and title
     * are identified as unique.
     *
     * @param $department array containing the user role data
     * @return int | String | array
     *
     * Usage:
     * $department => [
     *
     * ]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Department created successfully!",
     * $data => {"title": "LK HR", ...} //$data has a similar set of values as the input
     *  */

    public function createDepartment($department)
    {
        try {
            $validationResponse = ModelValidator::validate($this->departmentModel, $department, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('departmentMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $newDepartment = $this->store->insert($this->departmentModel, $department, true);
            return $this->success(201, Lang::get('departmentMessages.basic.SUCC_CREATE'), $newDepartment);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('departmentMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all departments.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All departments retrieved Successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */
    public function getAllDepartments($permittedFields, $options)
    {
        try {
            $filteredDepartments = $this->store->getAll(
                $this->departmentModel,
                $permittedFields,
                $options,
                [],
                [['isDelete', '=', false]]
            );
            return $this->success(200, Lang::get('departmentMessages.basic.SUCC_GETALL'), $filteredDepartments);
        } catch (Exception $e) {
            Log::error('DepartmentService.getAllDepartments: ' . $e->getMessage());
            return $this->error(500, Lang::get('departmentMessages.basic.ERR_GETALL'));
        }
    }

    /**
     * Following function retrives all departments without pagination, sorting and filtering.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All departments retrieved Successfully!",
     *      $data => [{"title": "LK HR", ...}, ...]
     * ]
     */

    public function getAllRawDepartments()
    {
        try {
            $departments = $this->store->getFacade()::table($this->departmentModel->getName())->get();
            return $this->success(200, Lang::get('departmentMessages.basic.SUCC_GETALL'), $departments);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('departmentMessages.basic.ERR_GETALL'), null);
        }
    }

    /**
     * Following function retrives a single department for a provided department_id.
     *
     * @param $id user department id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Department retrieved Successfully!",
     *      $data => {"title": "LK HR", ...}
     * ]
     */
    public function getDepartment($id)
    {
        try {
            $department = $this->store->getById($this->departmentModel, $id);
            if (is_null($department)) {
                return $this->error(404, Lang::get('departmentMessages.basic.ERR_NOT_EXIST'), null);
            }

            return $this->success(200, Lang::get('departmentMessages.basic.SUCC_GET'), $department);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('departmentMessages.basic.ERR_GET'), null);
        }
    }

    public function getDepartmentNameById($id)
    {
        try {
            $department = $this->store->getById($this->departmentModel, $id);
            if (is_null($department)) {
                return null;
            }
            return  $department->name;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }

    public function getEmployeesByDepartmentID($departmentID)
    {
        try {
            $employees = $this->store->getFacade()::table($this->employeeModel->getName())
                ->leftJoin($this->jobModel->getName(), 'employee.id', '=', 'employeeJob.employeeId')
                ->where('employeeJob.departmentId', $departmentID)
                ->where('employeeJob.effectiveDate', '<=', date('Y-m-d'))
                ->groupBy('employeeJob.effectiveDate')
                ->get();

            if (is_null($employees)) {
                return $this->error(404, Lang::get('departmentMessages.basic.ERR_NOT_EXIST'), null);
            }
            return $this->success(200, Lang::get('departmentMessages.basic.SUCC_GET'), $employees);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('departmentMessages.basic.ERR_GETALL'), null);
        }
    }

    private function getEmployeeCountByParentDepartmentId($departmentId)
    {
        try {
            $employees = $this->store->getFacade()::table($this->employeeModel->getName())
                ->leftJoin($this->jobModel->getName(), 'employee.id', '=', 'employeeJob.employeeId')
                ->where('employeeJob.departmentId', $departmentId)
                ->where('employeeJob.effectiveDate', '<=', $this->store->getFacade()::raw('CURRENT_TIMESTAMP'))
                ->orderBy('employeeJob.effectiveDate', 'desc')
                ->get();

            return count($employees);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    private function getEmployeeCount()
    {
        try {
            $allEmployees = $this->store->getAll(
                $this->employeeModel,
                [],
                [],
                [],
                [['isDelete', '=', false], ['isActive', '=', true]]
            );
            return count($allEmployees);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    private function checkDepartmentHasChildrenNode($departmentId)
    {
        try {
            $checkDepartmentHasChildrenNode = $this->store->getFacade()::table($this->departmentModel->getName())
                ->where('parentDepartmentId', $departmentId)
                ->get();

            return count($checkDepartmentHasChildrenNode);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    private function getEmployeeCountWithChildrenDepartment($departmentId)
    {
        try {
            $childDepartmentId = $this->store->getFacade()::table($this->departmentModel->getName())
                ->where('parentDepartmentId', $departmentId)
                ->pluck('id')->toArray();
            $parentDepartmentId = $this->store->getFacade()::table($this->departmentModel->getName())
                ->where('id', $departmentId)
                ->pluck('id')->toArray();
            $departmentArrayValue = array_merge($childDepartmentId, $parentDepartmentId);

            $employees = $this->store->getFacade()::table($this->employeeModel->getName())
                ->leftJoin($this->jobModel->getName(), 'employee.id', '=', 'employeeJob.employeeId')
                ->whereIn('employeeJob.departmentId', $departmentArrayValue)
                ->where('employeeJob.effectiveDate', '<=', $this->store->getFacade()::raw('CURRENT_TIMESTAMP'))
                ->orderBy('employeeJob.effectiveDate', 'desc')
                ->get();

            return count($employees);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
    /**
     * Following function retrieves all the departments nested according to a tree structure.
     *
     * @param $id user department id
     * @param $department array containing department data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Department updated Successfully",
     *      $data => {"title": "LK HR", ...} // has a similar set of data as entered to updating user.
     *
     */
    public function generateDepartmentTree()
    {
        try {
            // TODO : Future enchancement qChanging to handle by query 
            $allDepartments = json_decode($this->store->getFacade()::table("department")->where('isDelete', false)->orderBy("parentDepartmentId", "asc")->get(), true);
            $departmentTreeMap = []; // One element in this array consist of an array of departments that has common parentDepartment and the id of parent department.
            $departmentMap = [];

            if (is_null($allDepartments) || empty($allDepartments)) {
                return $this->error(404, Lang::get('departmentMessages.basic.ERR_NULL_GET_DEP_ORG_CHART'), null);
            }

            $currentParentId = $allDepartments[0]["parentDepartmentId"];
            $allDepartmentsCount = 0;
            foreach ($allDepartments as $department) {  //Generate a Displayable Department based on frontend requirements. children element is not instanciated at this moment.

                $displayableDepartment["id"] =  "" . $department["id"]; // This has to be casted to a string because the frontend requires a string
                $displayableDepartment["value"]["items"]["text"] = $this->checkDepartmentHasChildrenNode($department["id"]) != 0 ? "Employee count " . $this->getEmployeeCountWithChildrenDepartment($department["id"]) : "Employee count " . $this->getEmployeeCountByParentDepartmentId($department["id"]);
                $displayableDepartment["value"]["title"] = $department["name"];

                if ($department["parentDepartmentId"] == $currentParentId) {
                    array_push($departmentMap, $displayableDepartment);
                } else {
                    array_push($departmentTreeMap, array("parentDepartmentId" => $currentParentId, "children" => $departmentMap));
                    $currentParentId = $department["parentDepartmentId"];
                    $departmentMap = array();
                    array_push($departmentMap, $displayableDepartment);
                }
                if ($allDepartmentsCount == (count($allDepartments) - 1)) { //  Last elements for final parentDepartment in the allDepartments array are not added by the previous if else block. Therefore this must be executed.

                    array_push($departmentTreeMap, array("parentDepartmentId" => $currentParentId, "children" => $departmentMap));
                }
                $allDepartmentsCount++;
            }

            //This must be looped in reverse order.
            $treeMapIndex = count($departmentTreeMap) - 1;
            $normalCount = 0;
            while ($treeMapIndex >= 0) {
                $currentLeafDepartmentList = $departmentTreeMap[$treeMapIndex];

                for ($indexOne = (count($departmentTreeMap) - 1); $indexOne >= 0; $indexOne--) {
                    for ($indexTwo = (count($departmentTreeMap[$indexOne]["children"]) - 1); $indexTwo >= 0; $indexTwo--) {
                        if (($currentLeafDepartmentList["parentDepartmentId"] == $departmentTreeMap[$indexOne]["children"][$indexTwo]["id"])) {
                            $departmentTreeMap[$indexOne]["children"][$indexTwo]["children"] = $currentLeafDepartmentList["children"];
                        }
                    }
                }
                $treeMapIndex--;
            }

            $company["id"] = 0;
            $company["value"]["title"] = "Organization";
            $company["value"]["items"]["text"] = "Default parent Department" . "\n" . "Total Employees " . $this->getEmployeeCount();
            $company["children"] = $departmentTreeMap[0]["children"];

            if (is_null($company) || empty($company)) {
                return $this->error(404, Lang::get('departmentMessages.basic.ERR_NULL_GET_DEP_ORG_CHART'), null);
            }

            return $this->success(200, Lang::get('departmentMessages.basic.SUCC_GET_DEP_ORG_CHART'), $company);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('departmentMessages.basic.ERR_GET_DEP_ORG_CHART'), null);
        }
    }

    public function generateOrgTree()
    {
        try {
            $orgData = [];

            $orgHierarchyConfig = (array) $this->getConfigValue('organization_hierarchy');

            $selectedFields = ['id', 'name', 'parentEntityId', 'headOfEntityId', 'entityLevel'];

            $orgEntities = $this->store->getFacade()::table("orgEntity")->where('isDelete', false)->orderBy("parentEntityId", "DESC")->get($selectedFields);

            $childNodes = $orgEntities->whereNotNull('parentEntityId');

            $pluckedParentIds = $childNodes->pluck('parentEntityId')->unique()->sortDesc();

            foreach ($pluckedParentIds as $parentId) {
                $parentEntity = $orgEntities->firstWhere('id', $parentId);

                if (empty($parentEntity)) {
                    $orgEntities = $orgEntities->reject(function ($item) use ($parentId) {
                        return $item->parentEntityId == $parentId;
                    });
                    continue;
                }

                $children = $orgEntities->where('parentEntityId', $parentId);

                // entity ids for remove
                $ids = $children->pluck('id');
                $ids[] = $parentEntity->id;

                // get entityLevel
                $entityLevel = $children->pluck('entityLevel')->unique()->first();
                $entityLevelLabel = $orgHierarchyConfig[$entityLevel];

                // create new item
                $parent = $parentEntity;
                $parent->children = array_values($children->map(function ($item) use ($entityLevelLabel) {
                    $itemArray = (array) $item;
                    $itemArray['entityLevelLabel'] = $entityLevelLabel;
                    return $itemArray;
                })->toArray());

                $orgEntities = $orgEntities->whereNotIn('id', $ids);
                $orgEntities->push($parent);
            }

            $orgData = $orgEntities->toArray()[0];
            $orgData->entityLevelLabel = $orgHierarchyConfig[$orgData->entityLevel];

            return $this->success(200, Lang::get('departmentMessages.basic.SUCC_GET_DEP_ORG_CHART'), ['orgData' => $orgData, 'hierarchyConfig' => $orgHierarchyConfig]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('departmentMessages.basic.ERR_GET_DEP_ORG_CHART'), null);
        }
    }


    public function getManagerOrgChartData()
    {
        try {
            $orgData = [];

            $orgHierarchyConfig = (array) $this->getConfigValue('organization_hierarchy');

            $selectedFields = ['id', 'name', 'parentEntityId', 'headOfEntityId', 'entityLevel'];

            $orgEntities = $this->store->getFacade()::table("orgEntity")->where('isDelete', false)->orderBy("parentEntityId", "DESC")->get($selectedFields);

            $childNodes = $orgEntities->whereNotNull('parentEntityId');

            $pluckedParentIds = $childNodes->pluck('parentEntityId')->unique()->sortDesc();

            error_log(json_encode($pluckedParentIds));

            foreach ($pluckedParentIds as $parentId) {
                $parentEntity = $orgEntities->firstWhere('id', $parentId);

                if (empty($parentEntity)) {
                    $orgEntities = $orgEntities->reject(function ($item) use ($parentId) {
                        return $item->parentEntityId == $parentId;
                    });
                    continue;
                }

                $children = $orgEntities->where('parentEntityId', $parentId);

                // entity ids for remove
                $ids = $children->pluck('id');
                $ids[] = $parentEntity->id;

                // get entityLevel
                $entityLevel = $children->pluck('entityLevel')->unique()->first();
                $entityLevelLabel = $orgHierarchyConfig[$entityLevel];

                // create new item
                $parent = $parentEntity;
                $parent->children = array_values($children->map(function ($item) use ($entityLevelLabel) {
                    $itemArray = (array) $item;
                    $itemArray['entityLevelLabel'] = $entityLevelLabel;
                    return $itemArray;
                })->toArray());

                $orgEntities = $orgEntities->whereNotIn('id', $ids);
                $orgEntities->push($parent);
            }

            $orgData = $orgEntities->toArray()[0];
            $orgData->entityLevelLabel = $orgHierarchyConfig[$orgData->entityLevel];
            $entityWiseEmpData = $this->getEntityLevelWiseEmployeeData();

            return $this->success(200, Lang::get('departmentMessages.basic.SUCC_GET_DEP_ORG_CHART'), ['orgData' => $orgData, 'hierarchyConfig' => $orgHierarchyConfig, 'entityWiseEmpData' => $entityWiseEmpData]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('departmentMessages.basic.ERR_GET_DEP_ORG_CHART'), null);
        }
    }


    public function getManagerIsolatedOrgChartData($entityId)
    {
        try {
            $orgData = [];
            $relatedOrgEntityIds = $this->getParentEntityRelatedChildNodes((int)$entityId, null);
            array_push($relatedOrgEntityIds, (int)$entityId);

            $orgHierarchyConfig = (array) $this->getConfigValue('organization_hierarchy');

            $selectedFields = ['id', 'name', 'parentEntityId', 'headOfEntityId', 'entityLevel'];

            $orgEntities = $this->store->getFacade()::table("orgEntity")->where('isDelete', false)->orderBy("parentEntityId", "DESC")->whereIn('id', $relatedOrgEntityIds)->get($selectedFields);
            
            $childNodes = $orgEntities->whereNotNull('parentEntityId')->where('id','!=',$entityId);
            error_log(json_encode($childNodes));

            $pluckedParentIds = $childNodes->pluck('parentEntityId')->unique()->sortDesc();


            foreach ($pluckedParentIds as $parentId) {
                $parentEntity = $orgEntities->firstWhere('id', $parentId);

                if (empty($parentEntity)) {
                    $orgEntities = $orgEntities->reject(function ($item) use ($parentId) {
                        return $item->parentEntityId == $parentId;
                    });
                    continue;
                }

                $children = $orgEntities->where('parentEntityId', $parentId);

                // entity ids for remove
                $ids = $children->pluck('id');
                $ids[] = $parentEntity->id;

                // get entityLevel
                $entityLevel = $children->pluck('entityLevel')->unique()->first();
                $entityLevelLabel = $orgHierarchyConfig[$entityLevel];

                // create new item
                $parent = $parentEntity;
                $parent->children = array_values($children->map(function ($item) use ($entityLevelLabel) {
                    $itemArray = (array) $item;
                    $itemArray['entityLevelLabel'] = $entityLevelLabel;
                    return $itemArray;
                })->toArray());

                $orgEntities = $orgEntities->whereNotIn('id', $ids);
                $orgEntities->push($parent);
            }

            $orgData = $orgEntities->toArray()[0];
            $orgData->entityLevelLabel = $orgHierarchyConfig[$orgData->entityLevel];
            $entityWiseEmpData = $this->getEntityLevelWiseEmployeeData();

            return $this->success(200, Lang::get('departmentMessages.basic.SUCC_GET_DEP_ORG_CHART'), ['orgData' => $orgData, 'hierarchyConfig' => $orgHierarchyConfig, 'entityWiseEmpData' => $entityWiseEmpData]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('departmentMessages.basic.ERR_GET_DEP_ORG_CHART'), null);
        }
    }

    public function getEntityLevelWiseEmployeeData() {
        $selectedFields = ['id', 'name', 'parentEntityId', 'headOfEntityId', 'entityLevel'];
        $orgEntities = $this->store->getFacade()::table("orgEntity")->where('isDelete', false)->get($selectedFields);
        $entityWiseEmpData = [];

        foreach ($orgEntities as $key => $orgEntity) {
            $orgEntityId = $orgEntity->id;
            $orgEntityIds = $this->getParentEntityRelatedChildNodes((int)$orgEntityId, $orgEntities);
            array_push($orgEntityIds, $orgEntityId);

            $entityKey = 'entity-'.$orgEntityId;
            $maleCount = 0;
            $femaleCount = 0;
            $totalEmployeeCount = 0;
            $newRecruitsCount = 0;
            $resignCount = 0;

            $relatedEmployees =  DB::table('employee')
                ->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                ->where('employee.isDelete', false)
                ->whereIn('employeeJob.orgStructureEntityId', $orgEntityIds)->get(['employee.id','employee.genderId','employeeJob.employeeJourneyType', 'employeeJob.lastWorkingDate', 'employee.hireDate', 'employee.isActive']);
            $dateObj = Carbon::now();
            $start = $dateObj->startOfMonth()->format('Y-m-d');
            $end = $dateObj->endOfMonth()->format('Y-m-d');
            $startObj = Carbon::parse($start);
            $endObj = Carbon::parse($end);

            if (!is_null($relatedEmployees)) {

                
                foreach ($relatedEmployees as $key => $employee) {
                    $hireDate = Carbon::parse($employee->hireDate);
                    $lastWorkingDate = (!is_null($employee->lastWorkingDate)) ? Carbon::parse($employee->lastWorkingDate) : null;
                    if ($employee->isActive) {
                        $totalEmployeeCount += 1;
                        if ($hireDate->between($start, $end)) {
                            $newRecruitsCount += 1;
                        }

                        if (!empty($employee->genderId)) {
    
                            if ($employee->genderId == 1) {
                                $maleCount += 1;
                            } elseif ($employee->genderId == 2) {
                                $femaleCount += 1;
                            }
        
                        }
                    }


                    if (!is_null($lastWorkingDate)) {
                        if ($lastWorkingDate->between($start, $end) && $employee->employeeJourneyType == 'RESIGNATIONS') {
                            $resignCount += 1;
                        }
                    }

                    
                }
            }


            
            $entityWiseEmpData[] = [
                'entityId' => $orgEntityId,
                'headCount' => ($totalEmployeeCount < 10) ? '0'.$totalEmployeeCount : $totalEmployeeCount,
                'femaleCount' => ($femaleCount < 10) ? '0'.$femaleCount : $femaleCount,
                'maleCount' => ($maleCount < 10) ? '0'.$maleCount : $maleCount,
                'newRecruitsCount' => ($newRecruitsCount < 10) ? '0'.$newRecruitsCount: $newRecruitsCount,
                'resignCount' => ($resignCount < 10) ? '0'.$resignCount: $resignCount,
            ];            
        }

        return $entityWiseEmpData;

    }


    public function getParentEntityRelatedChildNodes($id, $items)
    {
        if (is_null($items)) {
            $selectedFields = ['id', 'name', 'parentEntityId', 'headOfEntityId', 'entityLevel'];
            $items = $this->store->getFacade()::table("orgEntity")->where('isDelete', false)->get($selectedFields);
        }
        $selectedFields = ['id', 'name', 'parentEntityId', 'headOfEntityId', 'entityLevel'];
        $items = $this->store->getFacade()::table("orgEntity")->where('isDelete', false)->get($selectedFields);

        $kids = [];
        foreach ($items as $key => $item) {
            $item = (array) $item;
            if ($item['parentEntityId'] === $id) {
                $kids[] = $item['id'];
                array_push($kids, ...$this->getParentEntityRelatedChildNodes($item['id'], $items));
            }
        }
        return $kids;
    }

    /**
     * Following function updates a department.
     *
     * @param $id user department id
     * @param $department array containing department data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Department updated Successfully",
     *      $data => {"title": "LK HR", ...} // has a similar set of data as entered to updating user.
     *
     */
    public function updateDepartment($id, $department)
    {
        try {
            $validationResponse = ModelValidator::validate($this->departmentModel, $department, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('departmentMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            $existingDepartment = $this->store->getById($this->departmentModel, $id);
            if (is_null($existingDepartment)) {
                return $this->error(404, Lang::get('departmentMessages.basic.ERR_NOT_EXIST'), null);
            }

            $result = $this->store->updateById($this->departmentModel, $id, $department);

            if (!$result) {
                return $this->error(502, Lang::get('departmentMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('departmentMessages.basic.SUCC_UPDATE'), $department);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('departmentMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function delete a department.
     *
     * @param $id department id
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Department deleted Successfully!",
     *      $data => {"title": "LK HR", ...}
     *
     */
    public function deleteDepartment($id)
    {
        try {
            $existingDepartment = $this->store->getById($this->departmentModel, $id);
            if (is_null($existingDepartment)) {
                return $this->error(404, Lang::get('departmentMessages.basic.ERR_NOT_EXIST'), null);
            }

            $recordExist = Util::checkRecordsExist($this->departmentModel, $id);

            if (!empty($recordExist)) {
                return $this->error(502, Lang::get('departmentMessages.basic.ERR_NOTALLOWED'), null);
            }

            $departmentModelName = $this->departmentModel->getName();
            $result = $this->store->getFacade()::table($departmentModelName)
                ->where('id', $id)
                ->update(['isDelete' => true]);


            if (!$result) {
                return $this->error(502, Lang::get('departmentMessages.basic.ERR_DELETE'), $id);
            }

            return $this->success(200, Lang::get('departmentMessages.basic.SUCC_DELETE'), $existingDepartment);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('departmentMessages.basic.ERR_DELETE'), null);
        }
    }

    public function addEntity($data)
    {
        try {
            $validationResponse = ModelValidator::validate($this->orgEntityModel, $data, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('orgEntityMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $orgHierarchyConfig = (array) $this->getConfigValue('organization_hierarchy');
            $entityLevel = $data['entityLevel'];
            if (!isset($orgHierarchyConfig[$entityLevel])) {
                return $this->error(400, Lang::get('orgEntityMessages.basic.ERR_ORG_HIRACHY'), $validationResponse);
            }

            $entity = $this->store->insert($this->orgEntityModel, $data, true);

            return $this->success(201, Lang::get('orgEntityMessages.basic.SUCC_CREATE'), $entity);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('orgEntityMessages.basic.ERR_CREATE'), null);
        }
    }

    public function editEntity($id, $data)
    {
        try {
            $validationResponse = ModelValidator::validate($this->orgEntityModel, $data, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('orgEntityMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            $existingEntity = $this->store->getById($this->orgEntityModel, $id);
            if (is_null($existingEntity)) {
                return $this->error(404, Lang::get('orgEntityMessages.basic.ERR_NOT_EXIST'), null);
            }

            $result = $this->store->updateById($this->orgEntityModel, $id, $data);

            if (!$result) {
                return $this->error(500, Lang::get('orgEntityMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('orgEntityMessages.basic.SUCC_UPDATE'), $data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('orgEntityMessages.basic.ERR_UPDATE'), null);
        }
    }

    public function deleteEntity($id)
    {
        try {
            $existingDepartment = $this->store->getById($this->orgEntityModel, $id);
            if (is_null($existingDepartment)) {
                return $this->error(404, Lang::get('orgEntityMessages.basic.ERR_NOT_EXIST'), null);
            }

            if (is_null($existingDepartment->parentEntityId)) {
                return $this->error(400, Lang::get('orgEntityMessages.basic.ERR_DELETE_ROOT'), null);
            }

            $parentEntityCount = $this->store->getFacade()::table($this->orgEntityModel->getName())->where('parentEntityId', $id)->where('isDelete', false)->count();

            if ($parentEntityCount > 0) {
                return $this->error(400, Lang::get('orgEntityMessages.basic.ERR_NOTALLOWED'), null);
            }

            $this->store->deleteById($this->orgEntityModel, $id, true);

            return $this->success(200, Lang::get('orgEntityMessages.basic.SUCC_DELETE'), $existingDepartment);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('orgEntityMessages.basic.ERR_DELETE'), null);
        }
    }

    public function getAllEntities()
    {
        try {
            $orgHierarchyConfig = (array) $this->getConfigValue('organization_hierarchy');

            $entities = $this->store->getFacade()::table($this->orgEntityModel->getName())
                ->where('isDelete', false)
                ->get(['id', 'name', 'parentEntityId', 'entityLevel', 'headOfEntityId']);
            return $this->success(200, Lang::get('orgEntityMessages.basic.SUCC_GETALL'), ['entities' => $entities, 'orgHierarchyConfig' => $orgHierarchyConfig]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('orgEntityMessages.basic.ERR_GETALL'), null);
        }
    }

    public function getEntity($id)
    {
        try {
            $orgHierarchyConfig = (array) $this->getConfigValue('organization_hierarchy');

            $entities = $this->store->getFacade()::table($this->orgEntityModel->getName())
                ->where('isDelete', false)
                ->get(['id', 'name', 'parentEntityId', 'entityLevel', 'headOfEntityId'])
                ->toArray();

            $nextId = $id;
            $response = [];

            do {
                $index = array_search($nextId, array_column($entities, 'id'));
                $entity = $entities[$index] ?? null;

                if (is_null($entity)) break;

                $response[$orgHierarchyConfig[$entity->entityLevel]] = $entity;
                $nextId = $entity->parentEntityId;
            } while ($nextId != null);

            return $this->success(200, Lang::get('orgEntityMessages.basic.SUCC_GETALL'), array_reverse($response));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('orgEntityMessages.basic.ERR_GETALL'), null);
        }
    }

    public function canDelete($entityLevel)
    {
        try {

            if ($entityLevel === 'level1') {
                return $this->success(200, Lang::get('orgEntityMessages.basic.SUCC_ENTITY_DELETE_CHECK'), ['canDelete' => false]);
            }

            $entities = $this->store->getFacade()::table($this->orgEntityModel->getName())
                ->where('entityLevel', $entityLevel)->count();

            return $this->success(200, Lang::get('orgEntityMessages.basic.SUCC_ENTITY_DELETE_CHECK'), ['canDelete' => $entities == 0]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('orgEntityMessages.basic.ERR_ENTITY_DELETE_CHECK'), null);
        }
    }
}
