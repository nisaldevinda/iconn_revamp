<?php
return [

    'basic' => [
        'SUCC_CREATE' => 'Successfully Saved',
        'SUCC_UPDATE' => 'Successfully Updated',
        'SUCC_DELETE' => 'Successfully Deleted ',
        'SUCC_SINGLE_RETRIVE' => 'Workflow state loaded successfully.',
        'SUCC_ALL_RETRIVE' => 'All workflow states loaded successfully.',
        'ERR_CREATE' => 'Cannot Save.Something went wrong,Try again',
        'ERR_UPDATE' => 'Cannot Update.Something went wrong,Try again',
        'ERR_SINGLE_RETRIVE' => 'Failed to load workflow state.',
        'ERR_ALL_RETRIVE' => 'Failed to load workflow states.',
        'ERR_DELETE' => 'Cannot Delete.Something went wrong,Try again',
        'ERR_INVALID_CREDENTIALS' => 'Workflow state name is invalid.',
        'ERR_NONEXISTENT_TERMINATION_REASON' => 'Workflow state does not exist',
        'ERR_HAS_LINKED_STATE_TRANSITION' => 'Cannot Delete.This workflow already linked with workflow state transition',
        'ERR_HAS_LINKED_WORKFLOW_INSTANCES' => 'Cannot Delete.This workflow already linked with workflow instances',
        'ERR_CANNOT_CHANGE_HAS_LINKED_WORKFLOW_INSTANCES' => 'Cannot Update Approver Level.This workflow already linked with pending workflow instances',
        'ERR_CANNOT_ADD_HAS_LINKED_WORKFLOW_INSTANCES' => 'Cannot Create New Approver Level.This workflow already linked with pending workflow instances',
        'ERR_CANNOT_DELETE_HAS_LINKED_WORKFLOW_INSTANCES' => 'Cannot Delete Approver Level.This workflow already linked with pending workflow instances',
        'ERR_CANNOT_DELETE_WF_NODE_HAS_LINKED_WORKFLOW_INSTANCES' => 'Cannot Delete workflow node.This workflow already linked with pending workflow instances',
    ],
];