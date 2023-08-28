import request from '@/utils/request';

/**
 * To get all Genders
 */

export async function getALlGender(params?: any) {
  return request('/api/genders', { params });
}

export async function addGender(params: any) {
  return request('api/genders', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateGender(params: any) {
  return request(`api/genders/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeGender(record: any) {
  return request(`api/genders/${record.id}`, {
    method: 'DELETE',
  },
  true
  );
}

