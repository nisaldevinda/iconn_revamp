import request from '@/utils/request';
import type { PayGradesTableParams } from '@/pages/EmployeeFeild/fragments/PayGrades/data.d';
import { modifyFilterParams } from '@/utils/utils';

/**
 * To get all PayGrades
 */

export async function queryPayGrades(params?: PayGradesTableParams) {
  return request('/api/payGrade', { params });
}

export function getPayGrade(id: String) {
  return request(`/api/payGrade/${id}`);
}

export async function addPayGrades(params: PayGradesTableParams) {
  return request('api/payGrade', {
    method: 'POST',
    data: {
      ...params,
    },
  });
}

export async function updatePayGrades(params: PayGradesTableParams) {
  return request(`api/payGrade/${params.id}`, {
    method: 'PUT',
    data: {
      ...params,
    },
  });
}

export async function removePayGrades(params: any) {
  return request(`api/payGrade/${params.id}`, {
    method: 'DELETE',
  });
}
