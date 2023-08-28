import request from '@/utils/request';

export async function getMyTeams(params: any) {
  return request(`/api/my-teams`, { params }, true);
}

export async function getAllEmployee(params?: any) {
  return await request('/api/my-teams-employees', {
    params,
  });
}

export async function addEmployee(data: any) {
  return await request(
    '/api/my-teams-employees',
    {
      method: 'POST',
      data,
    },
    true,
  );
}

export async function updateEmployee(id: string, data: any) {
  return await request(
    `/api/my-teams-employees/${id}`,
    {
      method: 'PUT',
      data,
    },
    true,
  );
}

export async function getEmployee(id: string) {
  return await request(`/api/my-teams-employees/${id}`, {}, true);
}


export async function deleteEmployee(id: string) {
  return await request(
    `/api/my-teams-employees/${id}`,
    {
      method: 'DELETE',
    },
    true,
  );
}

export async function getEmployeeFieldAccessPermission(id:any) {
  return await request(`/api/employees/${id}/permission?type=MANAGER`);
}

export async function createEmployeeMultiRecord(
  parentId: string,
  multirecordAttribute: string,
  data: any,
) {
  return await request(
    `/api/my-teams-employees/${parentId}/${multirecordAttribute}`,
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
    `/api/my-teams-employees/${parentId}/${multirecordAttribute}/${multirecordId}`,
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
    `/api/my-teams-employees/${parentId}/${multirecordAttribute}/${multirecordId}`,
    {
      method: 'DELETE',
    },
    true,
  );
}

export default {
  getMyTeams,
  getAllEmployee,
  updateEmployee,
  getEmployee,
  addEmployee,
  deleteEmployee,
  createEmployeeMultiRecord,
  updateEmployeeMultiRecord,
  deleteEmployeeMultiRecord,
  getEmployeeFieldAccessPermission
};
