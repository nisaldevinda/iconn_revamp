import request from '@/utils/request';

export async function getAllRace(params?: any) {
  return request('/api/races', { params });
}

export async function addRace(params: any) {
  return request('api/races', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateRace(params: any) {
  return request(`api/races/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeRace(record: any) {
  return request(`api/races/${record.id}`, {
    method: 'DELETE',
  },
  true
  );
}
