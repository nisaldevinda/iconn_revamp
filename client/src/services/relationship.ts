import request from '@/utils/request';

export async function getAllRelationships(params?: any) {
  return request('/api/relationships', { params });
}

export async function addRelationship(params: any) {
  return request('api/relationships', {
    method: 'POST',
    data:{...params}  },
  true
  );
}

export async function updateRelationship(params: any) {
  return request(`api/relationships/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeRelationship(record: any) {
  return request(`api/relationships/${record.id}`, {
    method: 'DELETE',
  },
  true
  );
}
