<?php

namespace App\Library;

abstract class RoleType
{
    const SUPER_ADMIN = 'SUPER_ADMIN';

    const GLOBAL_ADMIN = 'GLOBAL_ADMIN';

    const ADMIN = 'ADMIN'; // Admin role

    const MANAGER = 'MANAGER'; // Manager role

    const EMPLOYEE = 'EMPLOYEE'; // Employee role

    const OTHER = 'OTHER'; // Other role

    const GLOBAL_ADMIN_ID = 1; // Global Admin Role Id

    const SYSTEM_ADMIN_ID = 2; // Global Admin Role Id
}
