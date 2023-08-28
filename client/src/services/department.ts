import request from '@/utils/request';

export async function getAllDepartment(params?: any) {
  return request('/api/departments/', { params });
}

export async function addDepartment(params: any) {
  return request('api/departments/', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateDepartment(params: any) {
  return request(`api/departments/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeDepartment(record: any) {
  return request(`api/departments/${record.id}`, {
    method: 'DELETE',
  },
  true
  );
}

export async function getOrganizationTree() {
  return request('/api/departments-tree-generator');
}

export async function getManagerOrganizationChartData() {
  return request('/api/manager-org-chart-data');
}

export async function getManagerIsolatedOrganizationChartData(entityId:number) {
  return request(`/api/manager-isolated-org-chart-data/${entityId}`);
}

export async function getEmployeeData(departmentID: number) {
  return await request(`/api/departments-employee/${departmentID}/employees`)
}

export function addEntity(params: any) {
  return request(
    'api/org-entities',
    {
      method: 'POST',
      data: { ...params },
    },
    true,
  );
}

export function editEntity(params: any) {
  return request(
    `api/org-entities/${params.id}`,
    {
      method: 'PUT',
      data: { ...params },
    },
    true,
  );
}

export function deleteEntity(params: any) {
  return request(
    `api/org-entities/${params.id}`,
    {
      method: 'DELETE',
    },
    true,
  );
}

export function getAllEntities() {
  return request('api/org-entities');
}

export function getEntity(id: number) {
  return request(`api/org-entities/${id}`);
}

export function canDeleteEntity(entityLevel: string) {
  return request(
    `api/org-entities/${entityLevel}/can-delete`,
    {
      method: 'POST',
      data: {},
    },
    true,
  );
}