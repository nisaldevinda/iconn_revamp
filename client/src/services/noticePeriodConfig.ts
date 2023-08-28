import request from '@/utils/request';

export async function getAllNoticePeriodConfigs(params?: any) {
  return request('/api/notice-period-configs', { params });
}

export async function addNoticePeriodConfig(params: any) {
  return request('api/notice-period-configs', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateNoticePeriodConfig(params: any) {
  return request(`api/notice-period-configs/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeNoticePeriodConfig(record: any) {
  return request(`api/notice-period-configs/${record.id}`, {
    method: 'DELETE',
  },
  true
  );
}
