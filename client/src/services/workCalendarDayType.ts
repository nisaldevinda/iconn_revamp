import request from '@/utils/request';
// import { modifyFilterParams } from '@/utils/utils';
import { getAuthority } from '../utils/authority';

export async function getAllDayTypes(params?:any) {
  return request('/api/work-calendar-day-types', {
    params,
  });
}

export async function getBaseDayTypes(params?:any) {
  return request('/api/get-all-base-day-types', {
    params,
  });
}

export async function addDayType(data:any) {
  return request(
    '/api/work-calendar-day-types',
    {
      method: 'POST',
      data: {
        ...data
      },
    },
    true
  );
}

export async function updateDayType(record:any) {
  return request(
    `/api/work-calendar-day-types/${record.id}`,
    {
      method: 'PUT',
      data: {
        ...record
      },
    },
    true
  );
}

export async function removeDayType(record:any) {
  return request(
    `/api/work-calendar-day-types/${record.id}`,
    {
      method: 'DELETE',
      data: {
      },
    },
    true
  );
}

