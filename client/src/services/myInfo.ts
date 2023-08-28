import request from '@/utils/request';

export async function getAllEmployee() {
  return await request(`/api/myProfile`, {}, true);
}

export async function getEmployee(id: string) {
  return await request(`/api/myProfile`, {}, true);
}

export async function getEmployeeViewData() {
  return await request(`/api/myProfile/view`, {}, true);
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
  data['context'] = 'Profile update';
  data['id'] = id;
  let path = `/api/myProfile/update`;
  return await request(
    path,
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

export async function getEmployeeFieldAccessPermission(id: any) {
  return await request(`/api/employees/${id}/permission?type=EMPLOYEE`);
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
export async function getUpcomingBirthDays(params?: any) {
  return await request('/api/employees-birthdays', {
    params,
  });
}

export async function getUpcomingHiredDays(params?: any) {
  return await request('/api/employees-hired', {
    params,
  });
}

export async function createEmployeeMultiRecord(
  parentId: string,
  multirecordAttribute: string,
  data: any,
) {
  return await request(
    `/api/myProfile/${multirecordAttribute}`,
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
    `/api/myProfile/${multirecordAttribute}/${multirecordId}`,
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
    `/api/myProfile/${multirecordAttribute}/${multirecordId}`,
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

export default {
  updateEmployee,
  getEmployee,
  getEmployeeViewData,
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
  getEmployeeFieldAccessPermission,
};
