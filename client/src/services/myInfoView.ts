import request from '@/utils/request';

export async function updateEmployee(id: string, data: any) {
  return;
}

export async function getEmployee() {
  return await request(`/api/myProfile/view`, {}, true);
}

export async function createEmployeeMultiRecord(
  parentId: string,
  multirecordAttribute: string,
  data: any,
) {
  return;
}

export async function updateEmployeeMultiRecord(
  parentId: string,
  multirecordAttribute: string,
  multirecordId: number,
  data: any,
) {
  return;
}

export async function deleteEmployeeMultiRecord(
  parentId: string,
  multirecordAttribute: string,
  multirecordId: number,
) {
  return;
}

export async function getEmployeeFieldAccessPermission(id: any) {
  return {
    error: false,
    data: {
      readOnly: '*'
    }
  };
}

export default {
  updateEmployee,
  getEmployee,
  createEmployeeMultiRecord,
  updateEmployeeMultiRecord,
  deleteEmployeeMultiRecord,
  getEmployeeFieldAccessPermission,
};
