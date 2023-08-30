import request from '@/utils/request';

export async function getFile(key: number) {
  return await request(`/api/file/${key}`);
}

export async function uploadFile(data: any) {
  return request(
    '/api/file',
    {
      method: 'POST',
      data,
    },
    true,
  );
}
