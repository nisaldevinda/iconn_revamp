import request from '@/utils/request';

/**
 * To get all NoticeCategorys
 */

export async function getAllNoticeCategory(params?: any) {
  return request('/api/notice-categories', { params });
}

export async function addNoticeCategory(params: any) {
  return request('api/notice-categories', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateNoticeCategory(params: any) {
  return request(`api/notice-categories/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeNoticeCategory(record: any) {
  return request(`api/notice-categories/${record.id}`, {
    method: 'DELETE',
  },
  true
  );
}

