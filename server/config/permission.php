<?php

return [
    'roles' => [
        'EMPLOYEE',
        'MANAGER',
        'ADMIN'
    ],
    'workflows' => [
        'workflow-1' => 'Profile Request', // workflow- prefix with relevant contex id
        'workflow-2' => 'Leave Request',
        'workflow-3' => 'Time Change Request',
        'workflow-4' => 'Short Leave Request',
        'workflow-5' => 'Shift Change Request',
        'workflow-6' => 'Cancel Leave Request',
        'workflow-7' => 'Resignation Request',
        'workflow-8' => 'Cancel Short Leave Request',
        'workflow-9' => 'Claim Request',
        'workflow-10' => 'Post OT Request'
    ],
    // list of system permissions
    'permissions' => [
        'master-data-read' => 'Access Master Data',
        'master-data-write' => 'Master Data',
        'user-read-write' => 'User',
        'employee-create' => 'Add Employee',
        'employee-write' => 'Employee Profile',
        'payroll-read-write' => 'Employee Payroll',
        'employee-read' => 'Access Employee',
        'access-levels-read-write' => 'User Role',
        'workflow-management-read-write' => 'Workflow Management',
        'expense-management-read-write' => 'Expense Management',
        'financial-year-read-write' => 'Financial Year',
        'workflow-write' => 'Workflow Write',
        'workflow-read' => 'Workflow Read',
        'company-notice-read-write' => 'Notices',
        'invalid-attendance-update-admin-access' => 'Employee Invalid Attendance Update',
        'invalid-attendance-update-manager-access' => 'Team Invalid Attendance Update',
        'team-notice-read-write' => 'Notices',
        'document-manager-read-write' => 'Document Manager',
        'document-template-read' => 'Document Templates Read',
        'document-template-read-write' => 'Document Templates',
        'email-template-read-write' => 'Email Templates',
        'reports-read-write' => 'Reports',
        'company-info-read-write' => 'Company Info',
        'org-chart-read' => 'Organization Chart',
        'attendance-manager-access' => 'Attendance Manager',
        'attendance-employee-access' => 'Attendance Employee',
        'resignation-employee-access' => 'Employee Resignation Request',
        'post-ot-request-employee-access' => 'Post OT Request',
        'claim-request-employee-access' => 'Employee Claim Request',
        'attendance-admin-access' => 'Attendance Admin',
        'attendance-employee-summery' => 'Attendance Summery View',
        'employee-leave-request-access' => 'My Leave Requests',
        'manager-leave-request-access' => 'Team Leave Requests',
        'manager-org-chart-access' => 'Oraganization Chart',
        'admin-leave-request-access' => 'Admin Leave Requests',
        'access-leave-attachments' => 'Access Leave Attachments',
        'my-profile' => 'My Profile',
        'employee-profile-diff' => 'Employee Profile Diff',
        'my-request' => 'My Request',
        'employee-request' => 'Employee Request',
        'todo-request-access' => 'ToDo Request Access',
        'leave-request-cancel' => 'Cancel Leave Request',
        'my-teams-write' => 'Edit My Team',
        'my-teams-read' => 'My Team',
        'work-pattern-read-write' => 'Work Pattern',
        'work-schedule-read' => 'Work Schedule Read',
        'work-schedule-read-write' => 'Work Schedule',
        'work-calendar-read-write' => 'Work Calendar',
        'work-calendar-day-type-read-write' => 'Work Calendar Day Type',
        'pay-type-read-write' => 'Pay Type',
        'assign-leave' => 'Assign Leave',
        'admin-widgets' => 'Admin Widgets',
        'manager-widgets' => 'Manager Widgets',
        'employee-widgets' => 'Employee Widgets',
        'admin-reports' => 'Admin reports',
        'manager-reports' => 'Manager reports',
        'employee-reports' => 'Employee reports',
        'leave-request-comments-read-write' => 'Leave Request Comments',
        'bulk-upload-read-write' => 'Bulk Upload',
        'self-service-lock' => 'Self Service Lock',
        'leave-entitlement-read' => "Leave Entitlement Access",
        'leave-entitlement-write' => "Leave Entitlement",
        'leave-entitlement-report-access' => 'Leave Entitlement Report',
        'ess' => 'Employee Self Service', // employee specific permissions
        'upload-profile-picture' => 'Upload Profile Picture',
        'my-work-schedule' => "My Work Schedule",
        'manual-process' => 'Manual Process',
        'scheduled-jobs-log' => 'Scheduled Jobs Log',
        'leave-type-config' => 'Leave Type Configurations',
        'azure-active-directory' => 'Azure Active Directory',
        'form-builder' => 'Form Builder',
        'template-builder' => 'Template Builder',
        'work-shifts-read-write' => 'Work Shifts',
        'document-manager-employee-access' => 'Employee Document Manager Access',
        'shifts-assign-read-write' => 'Shifts Assign',
        'work-pattern-assign-read-write' => 'Work Pattern Assign',
        'shift-change' => 'Shift Change',
        'apply-team-leave' => 'Apply Leave For Employees',
        'covering-person-read' => 'Get Leave Covering Person',
        'config-resignation-process-read-write' => 'Resignation Process Configuration',
        'config-confirmation-process-read-write' => 'Confirmation Process Configuration',
        'my-leave-request' => 'My Leave Request',
        'my-attendance' => 'My Attendance',
        'my-info-view' => 'My Info View',
        'my-info-request' => 'My Info Request',
        'my-request-status' => 'My Request Status',
        'my-leaves' => 'My Leaves',
        'my-leave-entitlements' => 'My Leave Entitlements',
        'my-resignation-request' => 'My Resignation Request',
        'my-claim-request' => 'My Claim Request',
        'my-post-ot-request' => 'My Post OT Request',
        'my-shift-change-request' => 'My Shift Change Request',
        'billing' => 'Billing'
    ],
    // role permissions
    'rolePermissions' => [
        'GLOBAL_ADMIN' => [
            'permission-list' => [],
            'default-permissions' => [
                'master-data-write',
                'user-read-write',
                'employee-create',
                'employee-write',
                'payroll-read-write',
                'employee-read',
                'access-levels-read-write',
                'workflow-management-read-write',
                'expense-management-read-write',
                'financial-year-read-write',
                'workflow-write',
                'workflow-read',
                'document-template-read',
                'document-template-read-write',
                'reports-read-write',
                'master-data-read',
                'admin-leave-request-access',
                'leave-request-cancel',
                'org-chart-read',
                'company-info-read-write',
                'document-manager-read-write',
                'email-template-read-write',
                'employee-request',
                'todo-request-access',
                'work-pattern-read-write',
                'work-schedule-read-write',
                'work-schedule-read',
                'work-calendar-read-write',
                'work-calendar-day-type-read-write',
                'pay-type-read-write',
                'access-leave-attachments',
                'assign-leave',
                'employee-profile-diff',
                'admin-widgets',
                'admin-reports',
                'attendance-admin-access',
                'leave-request-comments-read-write',
                'bulk-upload-read-write',
                'leave-entitlement-read',
                'leave-entitlement-write',
                'leave-entitlement-report-access',
                'attendance-employee-summery',
                'upload-profile-picture',
                'manual-process',
                'scheduled-jobs-log',
                'leave-type-config',
                'form-builder',
                'self-service-lock',
                'template-builder',
                'work-shifts-read-write',
                'company-notice-read-write',
                'invalid-attendance-update-admin-access',
                'team-notice-read-write',
                'azure-active-directory',
                'shifts-assign-read-write',
                'work-pattern-assign-read-write',
                'config-resignation-process-read-write',
                'config-confirmation-process-read-write',
                'billing'
            ]
        ],
        'SYSTEM_ADMIN' => [
            'permission-list' => [],
            'default-permissions' => [
                'master-data-write',
                'master-data-read',
                'document-template-read',
                'access-levels-read-write',
                'workflow-management-read-write',
                'expense-management-read-write',
                'financial-year-read-write',
                'workflow-write',
                'workflow-read',
                'scheduled-jobs-log',
                'work-schedule-read',
                'manual-process'
            ]
        ],
        'ADMIN' => [
            // selectable permissions
            'permission-list' => [
                'master-data-write',
                'user-read-write',
                'employee-create',
                'employee-write',
                'payroll-read-write',
                'access-levels-read-write',
                'document-template-read-write',
                'reports-read-write',
                'document-manager-read-write',
                'email-template-read-write',
                'work-pattern-read-write',
                'work-schedule-read-write',
                'leave-entitlement-write',
                'manual-process',
                'leave-type-config',
                'form-builder',
                'self-service-lock',
                'template-builder',
                'work-shifts-read-write',
                'company-notice-read-write',
                'invalid-attendance-update-admin-access',
                'shifts-assign-read-write',
                'work-pattern-assign-read-write',
                'apply-team-leave',
                'config-resignation-process-read-write',
                'config-confirmation-process-read-write'
            ],
            // default permissions
            'default-permissions' => [
                'master-data-read',
                'document-template-read',
                'work-schedule-read',
                'employee-read',
                'access-leave-attachments',
                'employee-request',
                'todo-request-access',
                'workflow-read',
                'workflow-write',
                'attendance-admin-access',
                'admin-leave-request-access',
                'employee-profile-diff',
                'assign-leave',
                'admin-widgets',
                'admin-reports',
                'leave-request-comments-read-write',
                'leave-entitlement-read',
                'leave-entitlement-report-access',
                'attendance-employee-summery',
                'upload-profile-picture',
                'org-chart-read'
            ],
            'scopeAccess' => [
                'location' => []
            ],
            'workflows' => [
                'workflow-1',
                'workflow-2',
                'workflow-3',
                'workflow-4',
                'workflow-5',
                'workflow-6',
                'workflow-7',
                'workflow-8',
                'workflow-9',
                'workflow-10'
            ]
        ],
        'MANAGER' => [
            // selectable permissions
            'permission-list' => [
                'master-data-write',
                'team-notice-read-write',
                'shift-change',
                'invalid-attendance-update-manager-access'
                // 'employee-create',
                // 'employee-write',
                // 'document-template-read-write'
            ],
            // default permissions
            'default-permissions' => [
                'master-data-read',
                'document-template-read',
                'work-schedule-read',
                'attendance-manager-access',
                'attendance-employee-access',
                'access-leave-attachments',
                'manager-leave-request-access',
                'manager-org-chart-access',
                'employee-request',
                'todo-request-access',
                'employee-profile-diff',
                'workflow-write',
                'workflow-read',
                'my-teams-write',
                'my-teams-read',
                'manager-widgets',
                'manager-reports',
                'leave-request-comments-read-write',
                'attendance-employee-summery'
            ],
            'scopeAccess' => [
                'manager' => [
                    // 'direct' => "Direct",
                    'indirect' => "Indirect"
                ]
            ],
            'workflows' => [
                'workflow-1',
                'workflow-2',
                'workflow-3',
                'workflow-4',
                'workflow-5',
                'workflow-6',
                'workflow-7',
                'workflow-8',
                'workflow-9',
                'workflow-10'
            ]
        ],
        'EMPLOYEE' => [
            // selectable permissions
            'permission-list' => [
                // 'master-data-write',
                // 'employee-write',
                'my-leave-request',
                'my-attendance',
                'my-info-view',
                'my-info-request',
                'my-request-status',
                'my-leaves',
                'my-leave-entitlements',
                'my-resignation-request',
                'my-claim-request',
                'my-post-ot-request',
                'my-work-schedule',
                'my-shift-change-request',
            ],
            // default permissions
            'default-permissions' => [
                'master-data-read',
                'work-schedule-read',
                'document-template-read',
                'attendance-employee-access',
                'resignation-employee-access',
                'post-ot-request-employee-access',
                'claim-request-employee-access',
                'employee-leave-request-access',
                'access-leave-attachments',
                'my-profile',
                'my-request',
                'workflow-write',
                'employee-profile-diff',
                'todo-request-access',
                'workflow-read',
                'employee-widgets',
                'leave-request-cancel',
                'employee-reports',
                'leave-request-comments-read-write',
                'leave-entitlement-read',
                'ess',
                'upload-profile-picture',
                'my-work-schedule',
                'document-manager-employee-access',
                'covering-person-read',
            ]
        ]
    ]
];