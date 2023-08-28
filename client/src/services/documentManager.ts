import request from "@/utils/request";

export async function uploadFile(data: any) {
  return request('/api/documentmanager/files', {
    method: 'POST',
    data
  });
}

export async function getFolderHierarchy() {
  return request('/api/documentmanager/folder-hierarchy');
}

export async function getFileList(folderId?: number, employeeId?: number, data:any) {
  return request('/api/documentmanager/files', {params: {folderId, employeeId,data}});
}

export async function getFile(id: number) {
  return request(`/api/documentmanager/files/${id}`);
}

export async function deleteFile(id: number) {
  return request(`/api/documentmanager/files/${id}`, {
    method: 'DELETE',
  });
}

export async function addFolder(params: any) {
  return request('/api/documentmanager/add-folder', {
    method: 'POST',
    params
  });
}


export async function updateDocumentFile(id: number,data: any) {
  return request(`/api/documentmanager/update-files/${id}`, {
    method: 'PUT',
    data
  });
}
export async function documentAcknowledge(id: number,data: any) {
  return request(`/api/documentmanager/acknowledge-documnents/${id}`, {
    method: 'PUT',
    data
  });
}

export async function  getdocumentManagerList(params:any) {
  return await request('/api/documentmanager/reports', {
    params,
  });
}
export function getFilesInEmployeeFolders(params?:any) {
  return request(`/api/document-templates/employee-folder-files`, {
    params,
  });
}

export async function getMyFolderHierarchy() {
  return request('/api/documentmanager/my-folder-hierarchy');
}

export async function getMyFileList(folderId?: number, employeeId?: number) {
  return request('/api/documentmanager/my-files', {params: {folderId, employeeId}});
}

export async function getMyFile(id: number) {
  return request(`/api/documentmanager/my-files/${id}`);
}

export async function getDocumentAcknowledgeCount () {
  return request(`/api/document-manager/acknowledge-count`);
}
