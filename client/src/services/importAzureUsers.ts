import request from '@/utils/request';

export async function getStatus() {
  return await request('api/azure-active-directory/import-status');
}

export async function setup(data: any) {
  return request(
    `api/azure-active-directory/import-setup`,
    {
      method: 'POST',
      data,
    },
    true,
  );
}

export async function getFieldMap() {
  return await request('api/azure-active-directory/field-map');
}

export async function getConfig() {
  return await request('api/azure-active-directory/config');
}

export async function storeAuthConfig(data: any) {
  return request(
    `api/azure-active-directory/auth-config`,
    {
      method: 'POST',
      data,
    },
    true,
  );
}

export async function storeUserProvisioningConfig(data: any) {
  return request(
    `api/azure-active-directory/user-provisioning-config`,
    {
      method: 'POST',
      data,
    },
    true,
  );
}
