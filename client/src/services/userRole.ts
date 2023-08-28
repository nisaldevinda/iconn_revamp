import request from '@/utils/request';

export async function addUserRoles(params: any) {
  return request(
    'api/userRoles',
    {
      method: 'POST',
      data: {
        ...params,
      },
    },
    true
  );
}

export async function updateUserRoles(id:any,params: any) {
  return request(
    `/api/userRoles/${id}`, 
    {
      method: 'PUT',
      data: {
        ...params,
      },
    },
    true
  );
}

export async function queryUserRoles(params?: any) {
  return request(
    '/api/userRoles',
    {params},
    true
  );
}

export async function queryByUserRolesID(id :any) {
  return request(
    `/api/userRoles/${id}`,
    {},
    true
  );
}

export async function deleteByUserRolesID(record: any) {
  return request(
    `/api/userRoles/${record.id}`,
    {
      method:'DELETE',
    },
    true
  );
}

export async function queryUserRolesMeta() {
  return request('/api/userRolesMeta');
}

export async function getAccessManageFields() {
  return request(
    `/api/userRoles/access-management-fields`,
    {},
    true
  );
}

export async function getAccessManageMandotaryFields() {
  return request(
    `/api/userRoles/access-management-mandotary-fields`,
    {},
    true
  );
}

export async function getAdminRoles(params?: any) {
  return request(
    '/api/get-admin-roles',
    {params},
    true
  );
}
  
  
  