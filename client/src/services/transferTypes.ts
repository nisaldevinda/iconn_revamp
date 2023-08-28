import request from '@/utils/request';

export async function getAllTransferTypes(params?: any) {
  return request('/api/transfer-types', { params });
}

export async function addTransferTypes(params: any) {
  return request('api/transfer-types', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateTransferTypes(params: any) {
  return request(`api/transfer-types/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeTransferTypes(record: any) {
  return request(`api/transfer-types/${record.id}`, {
    method: 'DELETE',
  },
  true
  );
}
