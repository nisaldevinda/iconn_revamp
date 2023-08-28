import request from '@/utils/request';

export function getConfirmationProcess(id: String) {
  return request(`/api/confirmation-processes/${id}`);
}

export function createConfirmationProcess(data: any) {
  return request(
    '/api/confirmation-processes',
    {
      method: 'POST',
      data,
    },
    true,
  );
}

export function updateConfirmationProcess(id: String, data: any) {
  return request(
    `/api/confirmation-processes/${id}`,
    {
      method: 'PUT',
      data,
    },
    true,
  );
}

export function getConfirmationProcessList(params?: any) {
  return request(
    `/api/confirmation-processes`,
    {
      params,
    },
    true,
  );
}

export function deleteConfirmationProcess(record: any) {
  return request(
    `/api/confirmation-processes/${record.id}`,
    {
      method: 'DELETE',
    },
    true,
  );
}
