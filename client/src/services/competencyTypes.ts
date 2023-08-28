import request from '@/utils/request';

export async function getAllCompetencyTypes(params?: any) {
  return request('/api/competency-types/', { params });
}

export async function addCompetencyTypes(params: any) {
  return request('api/competency-types/', {
    method: 'POST',
    data: {
      ...params,
    },
  });
}

export async function updateCompetencyTypes(params: any) {
  return request(`api/competency-types/${params.id}`, {
    method: 'PUT',
    data: {
      ...params,
    },
  });
}

export async function removeCompetencyTypes(record: any) {
  return request(`api/competency-types/${record.id}`, {
    method: 'DELETE',
  });
}
