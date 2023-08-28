import request from '@/utils/request';


export async function getAllScheme(params?: any) {
  return request('/api/schemes', { params });
}

export async function addScheme(params: any) {
  return request('api/schemes', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateScheme(params: any) {
  return request(`api/schemes/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeScheme(record: any) {
  return request(`api/schemes/${record.id}`, {
    method: 'DELETE',
  },
  true
  );
}
