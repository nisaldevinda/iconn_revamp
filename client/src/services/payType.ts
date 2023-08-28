import request from '@/utils/request';
// import { modifyFilterParams } from '@/utils/utils';
import { getAuthority } from '../utils/authority';

export async function getAllPayTypes(params?:any) {
  return request('/api/pay-types', {
    params,
  });
}

export async function addPayType(data:any) {
  return request(
    '/api/pay-types',
    {
      method: 'POST',
      data: {
        ...data
      },
    },
    true
  );
}

export async function updatePayType(record:any) {
  return request(
    `/api/pay-types/${record.id}`,
    {
      method: 'PUT',
      data: {
        ...record
      },
    },
    true
  );
}

export async function removePayType(record:any) {
  return request(
    `/api/pay-types/${record.id}`,
    {
      method: 'DELETE',
      data: {
      },
    },
    true
  );
}

