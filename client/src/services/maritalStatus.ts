import request from '@/utils/request';

export async function getAllMaritalStatus(params?: any) {
  return request('/api/marital-status', { params });
}

export async function addMaritalStatus(params: any) {
  return request('api/marital-status', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateMaritalStatus(params: any) {
  return request(`api/marital-status/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeMaritalStatus(records: any) {
  return request(`api/marital-status/${records.id}`, {
    method: 'DELETE',
  },
  true 
  );
}
