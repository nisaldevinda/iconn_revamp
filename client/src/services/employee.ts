import request from '@/utils/request';

export async function getAllEmployee(params?: any) {
  return await request('/api/employees', {
    params,
  });
}

export async function getEmployee(id: string) {
  return await request(`/api/employees/${id}`, {}, true);
}

export async function checkShiftAllocated(id: string) {
  return await request(`/api/check-is-shift-allocated/${id}`, {}, true);
}

export async function addEmployee(data: any) {
  return await request(
    '/api/employees',
    {
      method: 'POST',
      data,
    },
    true,
  );
}

export async function updateEmployee(id: string, data: any) {
  return await request(
    `/api/employees/${id}`,
    {
      method: 'PUT',
      data,
    },
    true,
  );
}

export async function deleteEmployee(id: string) {
  return await request(
    `/api/employees/${id}`,
    {
      method: 'DELETE',
    },
    true,
  );
}

export async function getEmployeeFieldAccessPermission(id:any) {
  return await request(`/api/employees/${id}/permission?type=ADMIN`);
}

export async function getEmployeeCurrentDetails(id: string) {
  return await request(`/api/employee-current/${id}`, {}, true);
}

export async function updateMyInfo(data: any) {
  return await request(
    '/api/workflow',
    {
      method: 'POST',
      data,
    },
    true,
  );
}
export async function getUpcomingBirthDays(route?:any,params?: any) {
  return await request(`/api/${route}/employees-birthdays`, {
    params,
  });
}

export async function getUpcomingHiredDays(route?: any,params?: any) {
  return await request(`/api/${route}/employees-hired`, {
    params,
  });
}


export async function getMultiReocrdData(id?: any, multirecordAttribute?: string) {
  return await request(`/api/myProfile/${multirecordAttribute}/${id}`);
}

export async function createEmployeeMultiRecord(parentId: string, multirecordAttribute: string, data:any) {
  return await request(
    `/api/employees/${parentId}/${multirecordAttribute}`,
    {
      method: 'POST',
      data,
    },
    true,
  );
}

export async function updateEmployeeMultiRecord(
  parentId: string,
  multirecordAttribute: string,
  multirecordId: number,
  data: any,
) {
  return await request(
    `/api/employees/${parentId}/${multirecordAttribute}/${multirecordId}`,
    {
      method: 'PUT',
      data,
    },
    true,
  );
}

export async function deleteEmployeeMultiRecord(
  parentId: string,
  multirecordAttribute: string,
  multirecordId: number,
) {
  return await request(
    `/api/employees/${parentId}/${multirecordAttribute}/${multirecordId}`,
    {
      method: 'DELETE',
    },
    true,
  );
}

export async function getMyprofile() {
  return await request(`/api/myProfile`, {}, true);
}

export async function getEmployeeOrgChart() {
  return await request('/api/employee-chart');
}

export async function getFilteredEmployee(params?: any) {
  return await request('/api/employees-filtered', {
    params,
  });
}
export async function getRelationalDataSet(params?: any) {
  return await request('/api/myProfile/relational-dataset', {
    params,
  });
}

export async function getDataDiffForProfileUpdate(params?: any) {
  return await request('/api/employee-profile-data-diff', {
    params,
  });
}

export async function getEmployeeSideCardDetails(id: string) {
  return await request(`/api/employee-side-card-details/${id}`, {}, true);
}

export async function getEmployeeMetaId() {
  return await request(`/api/employees-current-auto-id`, {}, true);
}

export async function getEmployeeNumberFormat() {
  return await request(`/api/employee-number-format`);
}

export function addEmployeeNumberConfig(data:any) {
  return  request(`/api/add-employee-number-config`, {
    method: 'POST',
    data,
  })
}

export async function getNextEmployeeNumber(params?: any) {
  return await request(`/api/next-employee-number`, {
    params
  });
}
export default {
  updateEmployee,
  getEmployee,
  checkShiftAllocated,
  getAllEmployee,
  createEmployeeMultiRecord,
  updateEmployeeMultiRecord,
  deleteEmployeeMultiRecord,
  addEmployee,
  deleteEmployee,
  getEmployeeCurrentDetails,
  updateMyInfo,
  getUpcomingBirthDays,
  getUpcomingHiredDays,
  getMyprofile,
  getEmployeeFieldAccessPermission
};
