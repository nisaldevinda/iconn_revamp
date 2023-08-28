import request from '@/utils/request';

export async function getAllQualificationLevels(params?: any) {
  return request('/api/qualification-levels', { params });
}

export async function queryRawQualificationLevels() {
  return request('/api/qualification-levels-raw/');
}

export async function addQualificationLevel(params: any) {
  return request('api/qualification-levels', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateQualificationLevel(params: any) {
  return request(`api/qualification-levels/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeQualificationLevel(record: any) {
  return request(`api/qualification-levels/${record.id}`, {
    method: 'DELETE',
  },
  true
  );
}
