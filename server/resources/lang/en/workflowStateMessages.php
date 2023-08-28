<?php
return [

    'basic' => [
        'SUCC_CREATE' => 'Successfully Saved',
        'SUCC_UPDATE' => 'Successfully Updated',
        'SUCC_DELETE' => 'Successfully Deleted',
        'SUCC_SINGLE_RETRIVE' => 'Workflow state loaded successfully.',
        'SUCC_ALL_RETRIVE' => 'All workflow states loaded successfully.',
        'ERR_CREATE' => 'Cannot Save.Something went wrong,Try again',
        'ERR_UPDATE' => 'Cannot Update.Something went wrong,Try again',
        'ERR_SINGLE_RETRIVE' => 'Failed to load workflow state.',
        'ERR_ALL_RETRIVE' => 'Failed to load workflow states.',
        'ERR_DELETE' => 'Cannot Delete.Something went wrong,Try again',
        'ERR_INVALID_CREDENTIALS' => 'Workflow state name is invalid.',
        'ERR_NONEXISTENT_TERMINATION_REASON' => 'Workflow state does not exist',
        'ERR_IS_DEFAULT_STATE' => 'Cannot Delete.This workflow state is default system generated workflow state',
        'ERR_HAS_LINKED_WORKFLOWS' => 'Cannot Delete.This workflow state already linked with workflow',
        'ERR_HAS_LINKED_STATE_TRANSITION' => 'Cannot Delete.This workflow state already linked with workflow state transition',
    ],
];