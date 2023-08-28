<?php

namespace App\Library;

class Context
{
    /**
     * all permitted employee ids for auth user
     */
    private $permittedEmployeeIds = [];

    /**
     * permitted direct employee ids for manager role
     */
    private $directEmployeeIds = [];

    /**
     * permitted indirect employee ids for manager role
     */
    private $indirectEmployeeIds = [];

    /**
     * permitted employee ids for manager role
     */
    private $managerPermittedEmployeeIds = [];

    /**
     * permitted employee ids for admin role
     */
    private $adminPermittedEmployeeIds = [];

    /**
     * set permitted direct employee ids for manager role
     */
    public function setDirectEmployeeIds($employeeIds)
    {
        return $this->directEmployeeIds = $employeeIds;
    }

    /**
     * get permitted direct employee ids for manager role
     */
    public function getDirectEmployeeIds()
    {
        return $this->directEmployeeIds;
    }

    /**
     * set permitted indirect employee ids for manager role
     */
    public function setIndirectEmployeeIds($employeeIds)
    {
        return $this->indirectEmployeeIds = $employeeIds;
    }

    /**
     * get permitted indirect employee ids for manager role
     */
    public function getIndirectEmployeeIds()
    {
        return $this->indirectEmployeeIds;
    }

    /**
     * set permitted employee ids for admin role
     */
    public function setAdminPermittedEmployeeIds($employeeIds)
    {
        return $this->adminPermittedEmployeeIds = $employeeIds;
    }

    /**
     * get permitted employee ids for admin role
     */
    public function getAdminPermittedEmployeeIds()
    {
        return $this->adminPermittedEmployeeIds;
    }

    /**
     * get permitted employee ids for admin role
     */
    public function getManagerPermittedEmployeeIds()
    {
        return array_unique(array_merge($this->directEmployeeIds, $this->indirectEmployeeIds));
    }

    /**
     * get permitted employee ids for user
     */
    public function getPermittedEmployeeIds()
    {
        return array_unique(array_merge($this->adminPermittedEmployeeIds, $this->directEmployeeIds, $this->indirectEmployeeIds));
    }

    /**
     * check whether has permitted to access 
     */
    public function hasPermitted($employeeId)
    {
        return in_array($employeeId, array_unique(array_merge($this->adminPermittedEmployeeIds, $this->directEmployeeIds, $this->indirectEmployeeIds)));
    }

}
