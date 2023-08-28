import request from '@/utils/request';

export async function getLeaveTypesForApplyLeave() {
  return request('/api/leave-types-for-appliying');
}

export async function getLeaveTypesForAdminApplyLeaveForEmployee(employeeId?: number) {
  return request('/api/leave-types-for-appliying-for-employee', { params: {employeeId}});
}

export async function getLeaveTypesForAssignLeave(employeeId?: number) {
  return request('/api/leave-types-for-assign', { params: {employeeId} });
}

export async function getEmployeeEntitlementCount(date?: string) {
  return date
    ? request('/api/get-employee-entitlement-count', { params: {date} })
    : request('/api/get-employee-entitlement-count');
}

export async function getEntitlementCountByEmployeeId(params?: any) {
  return params
    ? request('/api/get-employee-entitlement-count', { params: params })
    : request('/api/get-employee-entitlement-count');
}

export async function addLeave(params: any) {
    return request(
      'api/leaves',
      {
        method: 'POST',
        data: {
          ...params,
        },
      },
      true
    );
}

export async function addShortLeave(params: any) {
    return request(
      'api/short-leaves',
      {
        method: 'POST',
        data: {
          ...params,
        },
      },
      true
    );
}

export async function calculateWorkingDaysCountForLeave(leaveTypeId: string, fromDate: any, toDate: any , employeeId?: any) {
  return request('/api/calculate-working-days-count-for-leave', { params: {leaveTypeId, fromDate, toDate, employeeId} });
}
export async function calculateWorkingDaysCountForShortLeave(date: any) {
  return request('/api/calculate-working-days-count-for-short-leave', { params: {date} });
}
export async function calculateWorkingDaysCountForShortLeaveAssign(date: any, employeeId: any) {
  return request('/api/calculate-working-days-count-for-short-leave', { params: {date, employeeId} });
}
export async function calculateWorkingDaysCountForLeaveAssign(leaveTypeId: string, fromDate: any, toDate: any, employeeId: any) {
  return request('/api/calculate-working-days-count-for-leave', { params: {leaveTypeId, fromDate, toDate, employeeId} });
}
export async function getShiftData(fromDate: any, toDate: any) {
  return request('/api/get-shift-data-for-leave-date', { params: {fromDate, toDate} });
}

export async function getShiftDataForAssignLeave(fromDate: any, toDate: any, employeeId: any) {
  return request('/api/get-shift-data-for-leave-date', { params: {fromDate, toDate, employeeId} });
}

export async function assignLeave(params: any) {
  return request(
    'api/assign-leave',
    {
      method: 'POST',
      data: {
        ...params,
      },
    },
    true
  );
}

export async function addTeamLeave(params: any) {
  return request(
    'api/team-leaves',
    {
      method: 'POST',
      data: {
        ...params,
      },
    },
    true
  );
}

export async function addTeamShortLeave(params: any) {
  return request(
    'api/team-short-leaves',
    {
      method: 'POST',
      data: {
        ...params,
      },
    },
    true
  );
}
export async function assignShortLeave(params: any) {
  return request(
    'api/assign-short-leave',
    {
      method: 'POST',
      data: {
        ...params,
      },
    },
    true
  );
}

export async function getAttachementList(params?: any) {
  return await request('/api/leave-attachment-list', { params });
}


export async function getShortLeaveAttachementList(params?: any) {
  return await request('/api/short-leave-attachment-list', { params });
}

export async function getEmployeeData(params?:any) {
  return await request('/api/attendanceSheet/employees', {
    params,
  });
}

export async function getEmployeeRequestManagerData(params?:any) {
  return await request('/api/leaveRequest/managerData', {
    params,
  });
}

export async function getEmployeeRequestEmployeeData(params?:any) {
  return await request('/api/leaveRequest/employeeData', {
    params,
  });
}

export async function getEmployeeRequestAdminData(params?:any) {
  return await request('/api/leaveRequest/adminData', {
    params,
  });
}

export async function getEmployeeShortLeaveDataSet(params?:any) {
  return await request('/api/short-leave/employeeShortLeaves', {
    params,
  });
}

export async function getEmployeeLeaveHistoryDataSet(params?:any) {
  return await request('/api/leaveRequest/employeeLeavesHistory', {
    params,
  });
}
export async function getAdminShortLeaveDataSet(params?:any) {
  return await request('/api/short-leave/adminShortLeaves', {
    params,
  });
}

export async function getAdminLeaveHistoryDataSet(params?:any) {
  return await request('/api/leaveRequest/adminLeavesHistory', {
    params,
  });
}

export async function addComment(params: any, id: any) {
  let path = 'api/leaveRequest/addComment/'+id;
  return request(
    path,
    {
      method: 'PUT',
      data: {
        ...params,
      },
    },
    true
  );
}

export async function accessibleWorkflowActions(workflowId: number, employeeId: number, params: any, instanceId: any) {
  return request(
    `/api/accessible-workflow-actions/${workflowId}/workflow/${employeeId}/employee/${instanceId}`,
    { params },
  );
}

export async function updateInstance(params: any) {

  return request(`api/workflow/${params.instanceId}`, {
      method: 'PUT',
      data: { ...params }
  });
}

export async function  getLeaveEntitlementUsage(params:any) {
  return await request('/api/leaveRequest/leaveEntitlementUsage', {
    params,
  });
}

export async function getLeaveTypes(params:any) {
  return request('/api/leave-types', {
    params,
  });
}
export async function getLeaveTypeWiseAccruals(params:any) {
  return request('/api/leave-type-wise-accruals', {
    params,
  });
}

export async function cancelLeave(id: any) {
  let path = 'api/leaveRequest/cancel-leave/'+id;
  return request(
    path,
    {
      method: 'PUT'
    },
    true
  );
}

export async function checkShortLeaveAccessabilityForCompany(params?:any) {
  return await request('/api/checkShortLeaveAccessabilityForCompany', {
    params,
  });
}

export async function cancelAdminAssignLeave(id: any) {
  let path = 'api/leaveRequest/cancel-admin-assign-leave/'+id;
  return request(
    path,
    {
      method: 'PUT'
    },
    true
  );
}
export async function cancelAdminAssignShortLeave(id: any) {
  let path = 'api/leaveRequest/cancel-admin-assign-short-leave/'+id;
  return request(
    path,
    {
      method: 'PUT'
    },
    true
  );
}

export async function cancelCoveringPersonBasedLeaveRequest(id: any) {
  let path = 'api/leaveRequest/cancel-covering-person-based-leave/'+id;
  return request(
    path,
    {
      method: 'PUT'
    },
    true
  );
}
export async function addLeaveType(params) {
    return await request(
      'api/leave-types',
      {
        method: 'POST',
        data: {
          ...params,
        },
      },
      true
    );
}

export async function updateLeaveCoveringState(params) {

  return request(`api/update-leave-covering-person-request`, {
      method: 'PUT',
      data: { ...params }
  });
}


export async function editLeaveType(params: any) {
  return request(`api/leave-types/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}
export async function deleteLeaveType(id:any){

  return await request(
    `/api/leave-types/${id}`,
    {
      method: 'DELETE',
    },
    true,
  );
}
export async function getLeaveTypesWorkingDays() {
  return request('/api/leave-types-working-days');
}

export async function getWhoCanApply(id) {
  return request(`/api/leave-types/who-can-apply/${id}`);
}
export async function createWhoCanApply(params: any) {
  return request('/api/leave-types/who-can-apply', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function getAllEmployeeGroups(){
  return request('/api/leave-employee-groups');
}

export async function getAllEmployeeGroupsByLeaveTypeId(params?: any) {
  return await request('/api/leave-employee-groups-by-leave-type', { params });
}

export async function updateEmployeeGroup(params: any) {
  return request(`/api/leave-employee-groups/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function deleteEmployeeGroup(id:any){

  return await request(
    `/api/leave-employee-groups/${id}`,
    {
      method: 'DELETE',
    },
    true,
  );
}

export async function saveLeaveTypeAcrueConfigs(params: any) {
  return request(`/api/leave-type-accrual-configs/${params.leaveTypeId}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function createLeaveTypeAcrueConfig(params: any) {
  return request(`/api/leave-accrual-config`, {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateLeaveTypeAcrueConfig(params: any) {
  return request(`/api/leave-accrual-config/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function getLeaveTypeAccrualConfigsByLeaveTypeId(params?: any) {
  return await request('/api/leave-type-accrual-configs', { params });
}

export async function exportLeaveRequestManagerData(params?:any) {
  return await request('/api/leaveRequest/export-manager-data', {
    method: 'POST',
    data:{...params}
  });
}

export async function exportLeaveRequestAdminData(params?:any) {
  return await request('/api/leaveRequest/export-admin-data', {
    method: 'POST',
    data:{...params}
  });
}

export async function cancelLeaveDates(params) {

  return request(`api/cancel-leave-requests/${params.leaveRequestId}`, {
      method: 'PUT',
      data: { ...params }
  });
}

export async function cancelShortLeave(params) {

  return request(`api/cancel-short-leave-requests/${params.shortLeaveRequestId}`, {
      method: 'PUT',
      data: { ...params }
  });
}
