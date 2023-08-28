import request from '@/utils/request';
// import { modifyFilterParams } from '@/utils/utils';
import { getAuthority } from '../utils/authority';

export async function getAllPeriodConfigs(params?:any) {
  return request('/api/get-all-self-service-lock-date-periods', {
    params,
  });
}

export async function addPeriodConfig(data:any) {
  return request(
    '/api/self-service-lock-date-periods',
    {
      method: 'POST',
      data: {
        ...data
      },
    },
    true
  );
}

export async function updatePeriodConfig(record:any) {
  return request(
    `/api/self-service-lock-date-periods/${record.id}`,
    {
      method: 'PUT',
      data: {
        ...record
      },
    },
    true
  );
}

export async function removePeriodConfig(record:any) {
  return request(
    `/api/self-service-lock-date-periods/${record.id}`,
    {
      method: 'DELETE',
      data: {
      },
    },
    true
  );
}

