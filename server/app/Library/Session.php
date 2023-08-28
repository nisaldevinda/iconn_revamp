<?php

namespace App\Library;

class Session
{

    public $user;

    public $company;

    public $permission;

    public $context;

    public $employee;

    public function __construct(Permission $permission, Context $context)
    {
        $this->permission = $permission;
        $this->context = $context;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setEmployee($employee)
    {
        $this->employee = $employee;
    }

    public function getEmployee()
    {
        return $this->employee;
    }

    public function setCompany($company)
    {
        $this->company = $company;
    }

    public function getCompany()
    {
        return $this->company;
    }

    public function setPermission($permission)
    {
        $this->permission = $permission;
    }

    public function getPermission()
    {
        return $this->permission;
    }

    public function setContext($context)
    {
        $this->context = $context;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function isGlobalAdmin()
    {
        if (isset($this->user->adminRoleId) && $this->user->adminRoleId == RoleType::GLOBAL_ADMIN_ID) {
            return true;
        } else {
            return false;
        }
    }

    public function isSystemAdmin()
    {
        if (isset($this->user->adminRoleId) && $this->user->adminRoleId == RoleType::SYSTEM_ADMIN_ID) {
            return true;
        } else {
            return false;
        }
    }

    public function getTenantId()
    {
        if (isset($this->company->tenantId)) {
            return $this->company->tenantId;
        } else {
            return null;
        }
    }
}
