import request from '@/utils/request';

export async function getAllPromotionTypes(params?: any) {
  return request('/api/promotion-types', { params });
}

export async function addPromotionTypes(params: any) {
  return request('api/promotion-types', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updatePromotionTypes(params: any) {
  return request(`api/promotion-types/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removePromotionTypes(record: any) {
  return request(`api/promotion-types/${record.id}`, {
    method: 'DELETE',
  },
  true
  );
}
