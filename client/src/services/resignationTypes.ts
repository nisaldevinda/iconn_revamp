import request from '@/utils/request';

export async function getAllResignationTypes(params?: any) {
  return request('/api/resignation-types', { params });
}

export async function addResignationTypes(params: any) {
  return request('api/resignation-types', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateResignationTypes(params: any) {
  return request(`api/resignation-types/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeResignationTypes(record: any) {
  return request(`api/resignation-types/${record.id}`, {
    method: 'DELETE',
  },
  true
  );
}
