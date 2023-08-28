import request from '@/utils/request';

export async function getAllJobTitles(params?: any) {
  return request('/api/job-titles', { params });
}

export async function addJobTitle(params: any) {
  return request('api/job-titles', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateJobTitle(params: any) {
  return request(`api/job-titles/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeJobTitle(record: any) {
  return request(`api/job-titles/${record.id}`, {
    method: 'DELETE',
  },
  true
  );
}
