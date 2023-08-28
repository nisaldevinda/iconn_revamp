import request from '@/utils/request';

export async function getAllTerminationReasons(params?: any) {
  return request('/api/resignation-reasons', { params });
}

export async function addTerminationReasons(params: any) {
  return request('api/resignation-reasons', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateTerminationReasons(params: any) {
  return request(`api/resignation-reasons/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeTerminationReasons(record: any) {
  return request(`api/resignation-reasons/${record.id}`, {
    method: 'DELETE',
  },
  true
  );
}
