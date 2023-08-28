import request from '@/utils/request';

export async function getAllQualificationInstitutions(params?: any) {
  return request('/api/qualification-institutions', { params });
}

export async function addQualificationInstitutions(params: any) {
  return request('api/qualification-institutions', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateQualificationInstitutions(params: any) {
  return request(`api/qualification-institutions/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeQualificationInstitutions(record: any) {
  return request(`api/qualification-institutions/${record.id}`, {
    method: 'DELETE',
  },
  true
  );
}
