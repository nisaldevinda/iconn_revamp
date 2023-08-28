import request from '@/utils/request';

/**
 * To get all audit trail
 */
export async function getAllAuditTrail(params?: any) {
  return request('/api/audit-trail', { params });
}
