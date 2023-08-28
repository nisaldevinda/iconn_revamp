import request from '@/utils/request';

export async function getAllCompetency(params?: any) {
  return request('/api/competency/', { params });
}

export async function addCompetency(params: any) {
  return request('api/competency/', {
    method: 'POST',
    data: {
      ...params,
    },
  });
}

export async function updateCompetency(params: any) {
  return request(`api/competency/${params.id}`, {
    method: 'PUT',
    data: {
      ...params,
    },
  });
}

export async function removeCompetency(record: any) {
  console.log(record)
  return request(`api/competency/${record.id}`, {
    method: 'DELETE',
  });
}
