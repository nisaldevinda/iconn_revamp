import request from '@/utils/request';

export async function getAllConfirmationReasons(params?: any) {
  return request('/api/confirmation-reasons', { params });
}

export async function addConfirmationReasons(params: any) {
  return request('api/confirmation-reasons', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateConfirmationReasons(params: any) {
  return request(`api/confirmation-reasons/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeConfirmationReasons(record: any) {
  return request(`api/confirmation-reasons/${record.id}`, {
    method: 'DELETE',
  },
  true
  );
}
