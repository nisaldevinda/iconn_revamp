import request from '@/utils/request';

export async function getCompany() {
  return await request('/api/companies/');
}

export async function updateCompany(id: string, data: any) {
  return request(
    `api/companies/${id}`,
    {
      method: 'PUT',
      data,
    },
    true,
  );
}

export async function getCompanyImages(type:string | null) {
  const url = type ? `/api/companies/images?type=${type}` : `/api/companies/images`;
  return request(url, {}, true);
}

export async function storeCompanyImages(type: string, data: any) {
  const url = type ? `/api/companies/images?type=${type}` : `/api/companies/images`;
  return request(
    url,
    {
      method: 'POST',
      data,
    },
    true,
  );
}
