import request from '@/utils/request';
// import { modifyFilterParams } from '@/utils/utils';
import { getAuthority } from '../utils/authority';

export async function getAllServiceLockConfigs(params?:any) {
  return request('/api/self-service-lock-configs', {
    params,
  });
}

export async function addServiceLockConfig(data:any) {
  return request(
    '/api/self-service-lock-configs',
    {
      method: 'POST',
      data: {
        ...data
      },
    },
    true
  );
}

export async function updateServiceLockConfig(record:any) {
  return request(
    `/api/self-service-lock-configs/${record.id}`,
    {
      method: 'PUT',
      data: {
        ...record
      },
    },
    true
  );
}

export async function removeServiceLockConfig(record:any) {
  return request(
    `/api/self-service-lock-configs/${record.id}`,
    {
      method: 'DELETE',
      data: {
      },
    },
    true
  );
}

