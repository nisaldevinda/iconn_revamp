import request from '@/utils/request';

/**
 * To get all dynamic-forms
 */

export async function getAllDynamicForm(params?: any) {
  return request('/api/dynamic-forms', { params });
}

export async function getDynamicForm(id: string) {
  return await request(`/api/dynamic-forms/${id}`, {}, true);
}

export async function addDynamicForm(params: any) {
  return request('api/dynamic-forms', {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateDynamicForm(params: any) {
  return request(`api/dynamic-forms/${params.modelPath}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function updateAlternativeLayout(path:string, layout: any) {
  return request(`api/dynamic-forms/update-alternative-layout/${path}`, {
    method: 'PUT',
    data:{...layout}
  },
  true
  );
}

export async function removeDynamicForm(record: any) {
  return request(`api/dynamic-forms/${record.id}`, {
    method: 'DELETE',
  },
  true
  );
}

export async function getModel(model: String, alternative?: string) {
  let response = await request(alternative
    ? `/api/models/${model}/${alternative}`
    : `/api/models/${model}`);

  return response;
}
