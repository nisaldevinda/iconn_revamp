import request from '@/utils/request';

export async function getAllEmployeeNumberConfigs(params?: any) {
  return request('api/employee-number-configurations', { params });
}

export function getEmployeeNumberConfigs(id: number) {
  return request(`api/employee-number-configurations/${id}`);
}

export async function addEmployeeNumberConfigs(params: any) {
  return request(
    'api/employee-number-configurations',
    {
      method: 'POST',
      data: { ...params },
    },
    true,
  );
}

export async function updateEmployeeNumberConfigs(params: any) {
  return request(
    `api/employee-number-configurations/${params.id}`,
    {
      method: 'PUT',
      data: { ...params },
    },
    true,
  );
}

export async function removeEmployeeNumberConfigs(record: any) {
  return request(
    `api/employee-number-configurations/${record.id}`,
    {
      method: 'DELETE',
    },
    true,
  );
}
