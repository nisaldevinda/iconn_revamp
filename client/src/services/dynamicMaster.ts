import request from '@/utils/request';


export async function getAllDynamicMasterData(modelName: string, params?: any) {
  return request(`api/dynamic/${modelName}`, { params });
}

export async function addDynamicMasterData(modelName: string, params: any) {
  return request(`api/dynamic/${modelName}`, {
    method: 'POST',
    data:{...params}
  },
  true
  );
}

export async function updateDynamicMasterData(modelName: string, params: any) {
  return request(`api/dynamic/${modelName}/${params.id}`, {
    method: 'PUT',
    data:{...params}
  },
  true
  );
}

export async function removeDynamicMasterData(modelName: string, record: any) {
  return request(`api/dynamic/${modelName}/${record.id}`, {
    method: 'DELETE',
  },true);
}
