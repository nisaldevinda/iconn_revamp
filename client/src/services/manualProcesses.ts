import request from '@/utils/request';

export async function getAllManualProcesses(params?: any) {
  return request('/api/manual-processes', { params });
}

export async function getManualProcessHistory(params?: any) {
  return request('/api/manual-process-history', { params });
}
export async function getLeaveAccrualEmployeeList(params?: any) {
  return request('/api/leave-accrual-employee-list', { params });
}

export async function runManualProcess(data: any) {
  return request(
    '/api/manual-processes',
    {
      method: 'POST',
      data: {
        ...data,
      },
    },
    true,
  );
}
