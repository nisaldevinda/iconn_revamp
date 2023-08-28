<?php
return [
    'basic' => [
        'SUCC_GET' => 'Leave type loaded successfully.',
        'ERR_GET' => 'Failed to load the leave type.',
        'SUCC_GETALL' => 'All leave types loaded successfully.',
        'ERR_GETALL' => 'Failed to load all leave types.',
        'SUCC_CREATE' => 'Successfully Saved',
        'ERR_CREATE' => 'Cannot Save.Something went wrong,Try again',
        'SUCC_UPDATE' => 'Successfully Updated',
        'ERR_UPDATE' => 'Cannot Update.Something went wrong,Try again',
        'SUCC_DELETE' => 'Successfully Deleted',
        'ERR_DELETE' => 'Cannot Delete.Something went wrong,Try again',
        'ERR_NOT_EXIST' => 'Leave Type not exist for the given data',
        'ERR_NOTALLOWED' => 'Cannot Delete.The record has dependent information',
        'ERR_HAS_DEPENDENT_LEAVES' => 'Can not Delete.This leave type has related leave requests',
        'ERR_HAS_DEPENDENT_LEAVE_ENTITLEMENTS' => 'Can not Delete.This leave type has related leave entitlements',
        'LEAVE_TYPE_EXIST' => 'Leave Type name is already existing',
        'ERR_CAN_NOT_CHANGE_PERIOD_TYPE' => 'Cannot Update.already have linked entitlements so cannot change leave period type',
    ],
];
