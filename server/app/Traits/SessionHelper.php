<?php

namespace App\Traits;

use App\Exceptions\Exception;
use Illuminate\Support\Facades\DB;

trait SessionHelper
{
    protected function getAllEmployeeIds()
    {
        return DB::table('employee')->pluck('id')->toArray();
    }

    protected function getAdminRolePermittedEmployeeIds($employeeId, $scopeOfAccess)
    {
        $locations = isset($scopeOfAccess['location']) ? $scopeOfAccess['location'] : [];

        return DB::table('employee')->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                ->where('employee.id', '!=', $employeeId)
                ->whereIn('locationId', $locations)
                ->pluck('employee.id')
                ->toArray();
    }

    protected function getManagerRoleDirectEmployeeIds($employeeId)
    {
        return DB::table('employee')->leftJoin('employeeJob', 'employeeJob.id', '=', 'employee.currentJobsId')
                ->where('reportsToEmployeeId', $employeeId)
                ->pluck('employee.id')
                ->toArray();
    }

    protected function getManagerRoleInDirectEmployeeIds($employeeId)
    {
        $result = DB::select(
                    'WITH RECURSIVE myTeams AS (SELECT employee.id,employee.currentJobsId FROM employee 
                    LEFT JOIN employeeJob on employee.currentJobsId = employeeJob.id WHERE employeeJob.reportsToEmployeeId=:parentId 
                    UNION ALL SELECT employee.id,employee.currentJobsId FROM employee 
                    LEFT JOIN employeeJob on employee.currentJobsId = employeeJob.id 
                    INNER JOIN myTeams ON myTeams.id = employeeJob.reportsToEmployeeId) 
                    SELECT myTeams.id FROM myTeams LEFT JOIN employee ON myTeams.id = employee.id',
                    ['parentId' => $employeeId]
                );

        return collect($result)->pluck('id')->toArray();
    }

    protected function getAdminRoleWorkflowPermissions($session, $redis)
    {
        try {
            if ($session->isGlobalAdmin()) {
                return ['employeeIds' => $this->getAllEmployeeIds(), 'workflows' => ['*']];
            }

            $user = $session->getUser();

            $adminRoleId = isset($user->adminRoleId) ? $user->adminRoleId : null;

            $employeeId = isset($user->employeeId) ? $user->employeeId : null;

            $userRole = $redis->getUserRole($adminRoleId);

            if (empty($userRole)) {
                return ['employeeIds' => [], 'workflows' => []];
            }

            // get permitted actions
            $scopeOdAccess = isset($userRole->customCriteria) ? json_decode($userRole->customCriteria, true) : [];
            $workFlows = isset($userRole->workflowManagementActions) ? json_decode($userRole->workflowManagementActions, true) : [];

            $contextIds = [];
            foreach($workFlows as $workFlow) {
                $contextIds[] =str_replace('workflow-', '', $workFlow);
            }

            return [
                'employeeIds' => $this->getAdminRolePermittedEmployeeIds($employeeId, $scopeOdAccess),
                'workflows' => $contextIds
            ];
        } catch (Exception $e) {
            return ['employeeIds' => [], 'workflows' => []];
        }
    }

    protected function getManagerRoleWorkflowPermissions($session, $redis)
    {
        try {
            $user = $session->getUser();

            $managerRoleId = isset($user->managerRoleId) ? $user->managerRoleId : null;

            $employeeId = isset($user->employeeId) ? $user->employeeId : null;

            $userRole = $redis->getUserRole($managerRoleId);

            if (empty($userRole)) {
                return ['employeeIds' => [], 'workflows' => []];
            }

            // get permitted actions
            $scopeOfAccess = isset($userRole->customCriteria) ? json_decode($userRole->customCriteria, true) : [];
            $workFlows = isset($userRole->workflowManagementActions) ? json_decode($userRole->workflowManagementActions, true) : [];

            $contextIds = [];
            foreach ($workFlows as $workFlow) {
                $contextIds[] = str_replace('workflow-', '', $workFlow);
            }

            $directEmployeeIds = $this->getManagerRoleDirectEmployeeIds($employeeId);
            $inDirectEmployeeIds = [];

            if (isset($scopeOfAccess['manager']) && in_array('indirect', $scopeOfAccess['manager'])) {
                $inDirectEmployeeIds = $this->getManagerRoleInDirectEmployeeIds($employeeId);
            }

            return [
                'employeeIds' => array_unique(array_merge($directEmployeeIds, $inDirectEmployeeIds)),
                'workflows' => $contextIds
            ];
        } catch (Exception $e) {
            return ['employeeIds' => [], 'workflows' => []];
        }
    }
}
