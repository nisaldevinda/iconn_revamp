import request from '@/utils/request';

export async function getAllNationalities(params?: any) {
  return request('/api/nationalities', { params });
}

export async function addNationality(params: any) {
  return request('api/nationalities', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateNationality(params: any) {
  return request(`api/nationalities/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeNationality(record: any) {
  return request(`api/nationalities/${record.id}`, {
    method: 'DELETE',
  },
  true
  );
}
