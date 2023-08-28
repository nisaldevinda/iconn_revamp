<?php
return [
    'basic' => [
        'SUCC_GET' => 'Attendance retrieved successfully.',
        'SUCC_GO_WF' => 'Time change request successfully went through the work flow.',
        'ERR_GET' => 'Failed to retrieve the attendance.',
        'SUCC_GETALL' => 'All attendances retrieved successfully.',
        'ERR_GETALL' => 'Failed to retrieve all attendances.',
        'SUCC_CREATE' => 'Attendance created successfully.',
        'ERR_CREATE' => 'Failed to create the attendance.',
        'SUCC_UPDATE' => 'Attendance updated successfully.',
        'ERR_UPDATE' => 'Failed to update the attendance.',
        'SUCC_DELETE' => 'Attendance deleted successfully.',
        'ERR_DELETE' => 'Failed to delete the attendance.',
        'ERR_NOT_EXIST' => 'Attendance not exist for given data',
        'ERR_SHIFT_NOT_EXIST' => 'Shift not exist.',
        'SUCC_GET_FILE' => 'Attendance excel file downloaded successfully.',
        'ERR_NOT_PERMITTED' => "Permission denied",
        'ERR_CANNOT_APPLY_TIME_CHANGE_DUE_TO_LOCKED_ATTENDANCE_RECORDS' => "Failed to apply time change request,because requested date related attendance record already locked for pay roll process",
        'ERR_HAS_LOCKED_SELF_SERVICE' => "Failed to apply time change request,because requested date are within the self service lock date range",
        'ERR_ALREADY_USE_FOR_OT_APPROVE_PROCESS' => "Failed to apply time change request,because attendance record for that you requesting is already use for post ot approved process.",
        'ERR_CANNOT_APPROVE_TIME_CHANGE_DUE_TO_LOCKED_ATTENDANCE_RECORDS' => "Failed to approve time change request,because requested date related attendance record already locked for pay roll process",
        'SUCC_CREATE_ATTENDANCE_LOG' => "Attendance data saved successfully"
    ],
];
