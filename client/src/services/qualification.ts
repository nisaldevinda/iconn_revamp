import request from '@/utils/request';

export async function getAllQualifications(params?: any) {
  return request('/api/qualifications', { params });
}

export async function addQualification(params: any) {
  return request('api/qualifications', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateQualification(params: any) {
  return request(`api/qualifications/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeQualification(record: any) {
  return request(`api/qualifications/${record.id}`, {
    method: 'DELETE',
  },
  true
  );
}
