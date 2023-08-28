import request from '@/utils/request';
// import { modifyFilterParams } from '@/utils/utils';
import { getAuthority } from '../utils/authority';

export async function getAllUser(params?:any) {
  // params = modifyFilterParams(params);
  return request('/api/users', {
    params,
  });
}

export async function getAllManagers(params?:any) {
  // params = modifyFilterParams(params);
  return request('/api/managers', {
    params,
  });
}

export async function getWorkflowPermittedManagers(params?:any) {
  return request('/api/workflow-permitted-managers', {
    params,
  });
}

export async function addUser(data:any) {
  return request(
    '/api/users',
    {
      method: 'POST',
      data: {
        ...data
      },
    },
    true
  );
}

export async function updateUser(record:any) {
  return request(
    `/api/users/${record.id}`,
    {
      method: 'PUT',
      data: {
        ...record
      },
    },
    true
  );
}

export async function removeUser(record:any) {
  return request(
    `/api/users/${record.id}`,
    {
      method: 'DELETE',
      data: {
      },
    },
    true
  );
}

export async function resetPasswordUser(id: number) {
  return request(
    `/api/users/${id}/reset-password`,
    {
      method: 'POST',
      data: {
        id
      },
    },
    true
  );
}

export async function queryCurrent(): Promise<any> {
  const authority = getAuthority();
  if (authority) {
    return request(`/api/users/${authority.userId}`);
  }
}

export async function queryNotices(): Promise<any> {
  return request('/api/notices');
}

export async function changeActiveStatus(record:any): Promise<any> {
  return request(
    `/api/users/${record.id}/change-active-status`,
    {
      method: 'PUT',
      data: {
        ...record
      },
    },
    true
  );
}

export async function changeUserPassword(record:any) {
  return request(
    `/api/users/change-user-password`,
    {
      method: 'POST',
      data: {
        ...record
      },
    },
    true
  );
}