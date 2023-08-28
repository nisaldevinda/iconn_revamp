<?php

namespace App\Library;

class Permission
{
    private $hasPermited = false;

    private $enableFieldFilter = true;

    private $role = null;

    private $fieldPermissions = [];

    public function setHasPermited($hasPermited)
    {
        $this->hasPermited = $hasPermited;
    }

    public function getHasPermited()
    {
        return $this->hasPermited;
    }

    public function setEnableFieldFilter($enableFieldFilter)
    {
        $this->enableFieldFilter = $enableFieldFilter;
    }

    public function getEnableFieldFilter()
    {
        return $this->enableFieldFilter;
    }

    public function setRole($role)
    {
        $this->role = $role;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setFieldPermissions($fieldPermissions)
    {
        $this->fieldPermissions = $fieldPermissions;
    }

    public function getFieldPermissions()
    {
        return $this->fieldPermissions;
    }

    public function hasEnabledFieldFilter()
    {
        return $this->enableFieldFilter;
    }

    public function check()
    {
        return $this->hasPermited;
    }

    public function readableFields($modelName)
    {
        if (isset($this->fieldPermissions[$modelName])) {
            $modelPermission = $this->fieldPermissions[$modelName];
            $readPermissions = !empty($modelPermission['viewOnly']) ? $modelPermission['viewOnly'] : ['id'];
            $writePermissions = !empty($modelPermission['canEdit']) ? $modelPermission['canEdit'] : ['id'];
            $readable = array_unique(array_merge($readPermissions, $writePermissions));
            // handle special fields
            if ($modelName == 'employee') {
                $readable = array_diff($readable, ['employeeJourney', 'employeeSalarySection']);
            }
            return $readable;
        }
        return ['*'];
    }

    public function selectedReadableFields($modelName, $selectedFields)
    {
        if (isset($this->fieldPermissions[$modelName])) {
            // get readable fields
            $modelPermission = $this->fieldPermissions[$modelName];
            $readPermissions = !empty($modelPermission['viewOnly']) ? $modelPermission['viewOnly'] : ['id'];
            $writePermissions = !empty($modelPermission['canEdit']) ? $modelPermission['canEdit'] : ['id'];
            $readable = array_unique(array_merge($readPermissions, $writePermissions));

            // handle special fields
            if ($modelName == 'employee') {
                $readable = array_diff($readable, ['employeeJourney', 'employeeSalarySection']);
            }

            // check check whether '*' exist
            if (in_array('*', $selectedFields)) {
                return $readable;
            }
            return array_intersect($readable, $selectedFields);
        }
        return $selectedFields;
    }

    public function writeableFields($modelName, $modelAttributes = [])
    {
        if (isset($this->fieldPermissions[$modelName])) {
            $modelPermission = $this->fieldPermissions[$modelName];
            $writePermissions = !empty($modelPermission['canEdit']) ? $modelPermission['canEdit'] : [];
            return $writePermissions;
        }
        return $modelAttributes;
    }
}