import request from '@/utils/request';
import type { SalaryComponentTableParams } from '@/pages/EmployeeFeild/fragments/SalaryComponents/data.d';

/**
 * To get all SalaryComponents
 */

export async function querySalaryComponent(params?: SalaryComponentTableParams) {
  
  return request('/api/salaryComponents', { params });
}

export async function addSalaryComponent(params: SalaryComponentTableParams) {
  return request('api/salaryComponents', {
    method: 'POST',
    data: {
      ...params,
    },
  });
}

export async function updateSalaryComponent(params: SalaryComponentTableParams) {
  return request(`api/salaryComponents/${params.id}`, {
    method: 'PUT',
    data: {
      ...params,
    },
  });
}

export async function removeSalaryComponent(record: any) {
  return request(`api/salaryComponents/${record.id}`, {
    method: 'DELETE',
  });
}
