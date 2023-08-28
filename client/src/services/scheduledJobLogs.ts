import request from '@/utils/request';

export async function getAllScheduledJobLogHistory(params?: any) {
  return request('/api/scheduled-jobs-logs-history', { params });
}