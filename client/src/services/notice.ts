import request from '@/utils/request';

export async function getNotice(id: string) {
  return await request(`/api/notices/${id}`, {}, true);
}

export async function getAllNotices(params?: any) {
  return request('/api/notices', {
    params,
  });
}

export async function addNotice(data: any) {
  return request(
    '/api/notices',
    {
      method: 'POST',
      data: {
        ...data,
      },
    },
    true,
  );
}

export async function updateNotice(id: string, data: any) {
  return await request(
    `/api/notices/${id}`,
    {
      method: 'PUT',
      data,
    },
    true,
  );
}

export async function deleteNotice(id: String) {
  return await request(
    `/api/notices/${id}`,
    {
      method: 'DELETE',
    },
    true,
  );
}

export async function getLastPublishedNotices(route?:any,params?: any) {
  return request(`/api/${route}/dashboard-notices`, {
    params,
  });
}
