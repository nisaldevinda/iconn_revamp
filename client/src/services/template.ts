import request from '@/utils/request';

export async function getFormTemplate(id: string) {
  return await request(`/api/form-templates/${id}`, {}, true);
}

export async function getAllFormTemplates(params?: any) {
  return request('/api/form-templates', {
    params,
  });
}

export async function addFormTemplates(data: any) {
  return request(
    '/api/form-templates',
    {
      method: 'POST',
      data: {
        ...data,
      },
    },
    true,
  );
}

export async function updateFormTemplates(id: string, data: any) {
  return await request(
    `/api/form-templates/${id}`,
    {
      method: 'PUT',
      data,
    },
    true,
  );
}

export async function updateFormTemplateStatus(data: any) {
    return await request(
      `/api/update-form-template-status`,
      {
        method: 'PUT',
        data,
      },
      true,
    );
  }

export async function deleteFormTemplates(id: String) {
  return await request(
    `/api/form-templates/${id}`,
    {
      method: 'DELETE',
    },
    true,
  );
}

export function getFormTemplateInstance(instanceHash: string) {
  return request(`/api/form-templates/${instanceHash}/instance`, {}, true);
}

export function updateFormTemplateInstance(id: string, data: any) {
  return request(
    `/api/form-templates/${id}/instance`,
    {
      method: 'PUT',
      data,
    },
    true,
  );
}

export function getFormTemplateJobInstances(jobId: string) {
  return request(`/api/form-templates/${jobId}/job-instances`, {}, true);
}
