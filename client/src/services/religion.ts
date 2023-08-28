import request from '@/utils/request';


export async function getAllReligion(params?: any) {
  return request('/api/religions', { params });
}

export async function addReligion(params: any) {
  return request('api/religions', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateReligion(params: any) {
  return request(`api/religions/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeReligion(record: any) {
  return request(`api/religions/${record.id}`, {
    method: 'DELETE',
  },
  true
  );
}
