import request from '@/utils/request';

export async function getEmployeeList(scope?: string | null) {
  if (scope === null || scope === undefined) {
    return await request(`/api/employees-list`);
  }
  return await request(`/api/employees-list?scope=${scope}`);
}

export async function getAllEmployeeList(scope?: string | null) {
  if (scope === null || scope === undefined) {
    return await request(`/api/all-employees-list`);
  }
  return await request(`/api/all-employees-list?scope=${scope}`);
}

export async function getEmployeeListByEntityId(scope?: string, entityId?: any) {
  return await request(`/api/employees-list-by-entity-id?scope=${scope}&entityId=${entityId}`);
}

export async function getEmployeeListForCoveringPerson() {
  return await request(`/api/get-emp-list-for-covering-person`);
}

export async function getEmployeeListForClaimAllocation(params?: any) {
  return await request(`/api/get-emp-list-for-claim-allocation`,{ params });
}

export async function getOtPayTypeList(scope: string | null) {
  return await request(`/api/get-ot-pay-type-list?scope=${scope}`);
}

// get the list of Unassigned Employees  to Users
export async  function getUnAsssignedEmployees (params?: any) {
  return await request('/api/get-unassigned-employees-list',{ params });
}

// get the list of managers in the company
export async  function getManagerList () {
  return await request('/api/managers');
}

// get the list of subordinates for the selected Manager Id
export async  function getSubordinatesList (id: String) {
  return await request(`/api/get-subordinates-list/${id}`);
}

// get employee list for the selected DepartmentId and LocationId
export async  function getEmployeeListForDepartmentAndLocation (params :any) {
  return await request('/api/get-emp-list-for-department-and-location',{ params });
}

// get employee list for the selected department
export async  function getEmployeeListForDepartment (id: string) {
  return await request(`/api/get-emp-list-for-department/${id}`);
}

// get employee list for the selected Location
export async  function getEmployeeListForLocation (id: string) {
  return await request(`/api/get-emp-list-for-location/${id}`);
}
export async function getLeaveTypesList(scope: string | null) {
  if (scope === null) {
    return await request(`/api/leave-type-list`);
  }
  return await request(`/api/leave-type-list?scope=${scope}`);
}

export async function getRootEmployees() {
  return await request ('/api/get-employees-root-nodes')
}

export async function getUserList() {
  return await request(`/api/user-list`);
}
