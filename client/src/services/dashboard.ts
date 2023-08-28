import request from '@/utils/request';

export async function updateDashboard(data: any) {
  return await request(
    `/api/dashboard`,
    {
      method: 'PUT',
      data,
    },
    true,
  );
}

export async function getDashboard() {
  return await request(
    `/api/dashboard`,
    {
      method: 'GET',
    },
    true,
  );
}
