import request from '@/utils/request';
// import { modifyFilterParams } from '@/utils/utils';
import { getAuthority } from '../utils/authority';

export async function getAllDayTypes(params?:any) {
  return request('/api/work-calendar-day-types', {
    params,
  });
}

export async function checkTimeBasePayConfigState(params?:any) {
  return request('/api/get-time-base-pay-config-state', {
    params,
  });
}



export async function setWorkShiftPayConfigs(record:any) {
  return request(
    `/api/work-shift-pay-configuration/${record.id}`,
    {
      method: 'PUT',
      data: {
        ...record
      },
    },
    true
  );
}

export async function getWorkShiftPayConfigs(id:any) {
    return request(
      `/api/work-shift-pay-configuration/${id}`,
      {
        method: 'GET',
      },
      true
    );
  }



