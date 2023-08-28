import request from '@/utils/request';

export async function getAllEmploymentStatus(params?: any) {
  return request('/api/employment-status', { params });
}

export async function getEmploymentStatus(id?: number) {
  return request(`api/employment-status/${id}`);
}

export async function addEmploymentStatus(params: any) {
  return request('api/employment-status', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateEmploymentStatus(params: any) {
  return request(`api/employment-status/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeEmploymentStatus(record: any) {
  return request(`api/employment-status/${record.id}`, {
    method: 'DELETE',
  },
  true);
}
