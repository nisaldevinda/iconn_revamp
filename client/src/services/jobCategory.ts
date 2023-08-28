import request from '@/utils/request';

export async function getAllJobCategories(params?: any) {
  return request('/api/job-categories', { params });
}

export async function addJobCategories(params: any) {
  return request('api/job-categories', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateJobCategories(params: any) {
  return request(`api/job-categories/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeJobCategories(record: any) {
  return request(`api/job-categories/${record.id}`, {
    method: 'DELETE',
  },
  true
  );
}
