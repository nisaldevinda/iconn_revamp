import request from '@/utils/request';

export async function downloadTemplate(params?: any) {
  return request('api/bulk-upload/download-template', { params }, true);
}

export async function downloadLeaveTemplate() {
  return request('api/bulk-upload/download-leave-template', {}, true);
}

export async function uploadTemplate(params: any) {
  return request(
    'api/bulk-upload/upload-template',
    {
      method: 'POST',
      data: {
        ...params,
      },
    },
    true,
  );
}

export async function uploadLeaveTemplate(params: any) {
  return request(
    'api/bulk-upload/upload-leave-template',
    {
      method: 'POST',
      data: {
        ...params,
      },
    },
    true,
  );
}


export async function saveUploadedLeaveData(params: any) {
  return request(
    'api/bulk-upload/save-validated-leave-entitlement-data',
    {
      method: 'POST',
      data: {
        ...params,
      },
    },
    true,
  );
}

export async function getAllBulkUploadedHistory() {
  return request('api/bulk-upload/upload-history', {}, true);
}

export async function getFileObject(id?: any) {
  return request(`api/bulk-upload/upload-history/${id}/file-object`, {}, true);
}

export async function downloadSalaryIncrementTemplate(params?: any) {
  return request('api/bulk-upload/salary-increment/template', { params }, true);
}

export async function uploadSalaryIncrementSheet(params: any) {
  return request(
    'api/bulk-upload/salary-increment/upload',
    {
      method: 'POST',
      data: {
        ...params,
      },
    },
    true,
  );
}

export async function completeSalaryIncrementProcess(params: any) {
  return request(
    'api/bulk-upload/salary-increment/finish',
    {
      method: 'POST',
      data: {
        ...params,
      },
    },
    true,
  );
}

export async function getSalaryIncrementUploadHistory() {
  return request('api/bulk-upload/salary-increment/history', {}, true);
}

export async function rollbackSalaryIncrementUpload(id: any) {
  return request(`api/bulk-upload/salary-increment/rollback/${id}`, {
    method: 'DELETE',
  },
  true
  );
}

export async function getEmployeePromotionSupportData(params?: any) {
  return request('api/bulk-upload/employee-promotion/support', { params }, true);
}

export async function downloadEmployeePromotionTemplate(params?: any) {
  return request('api/bulk-upload/employee-promotion/template', { params }, true);
}

export async function uploadEmployeePromotionSheet(params: any) {
  return request(
    'api/bulk-upload/employee-promotion/upload',
    {
      method: 'POST',
      data: {
        ...params,
      },
    },
    true,
  );
}

export async function completeEmployeePromotionProcess(params: any) {
  return request(
    'api/bulk-upload/employee-promotion/finish',
    {
      method: 'POST',
      data: {
        ...params,
      },
    },
    true,
  );
}

export async function getEmployeePromotionUploadHistory() {
  return request('api/bulk-upload/employee-promotion/history', {}, true);
}

export async function rollbackEmployeePromotionUpload(id: any) {
  return request(`api/bulk-upload/employee-promotion/rollback/${id}`, {
    method: 'DELETE',
  },
  true
  );
}

export async function getEmployeeTransferSupportData(params?: any) {
  return request('api/bulk-upload/employee-transfer/support', { params }, true);
}

export async function downloadEmployeeTransferTemplate(params?: any) {
  return request('api/bulk-upload/employee-transfer/template', { params }, true);
}

export async function uploadEmployeeTransferSheet(params: any) {
  return request(
    'api/bulk-upload/employee-transfer/upload',
    {
      method: 'POST',
      data: {
        ...params,
      },
    },
    true,
  );
}

export async function completeEmployeeTransferProcess(params: any) {
  return request(
    'api/bulk-upload/employee-transfer/finish',
    {
      method: 'POST',
      data: {
        ...params,
      },
    },
    true,
  );
}

export async function getEmployeeTransferUploadHistory() {
  return request('api/bulk-upload/employee-transfer/history', {}, true);
}

export async function rollbackEmployeeTransferUpload(id: any) {
  return request(`api/bulk-upload/employee-transfer/rollback/${id}`, {
    method: 'DELETE',
  },
  true
  );
}
