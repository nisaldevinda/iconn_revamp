import request from '@/utils/request';


export async function getAllDivisions(params?: any) {
  return request('/api/divisions', { params });
}

export async function addDivision(params: any) {
  return request('api/divisions', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateDivision(params: any) {
  return request(`api/divisions/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeDivision(record: any) {
  return request(`api/divisions/${record.id}`, {
    method: 'DELETE',
  },true);
}
