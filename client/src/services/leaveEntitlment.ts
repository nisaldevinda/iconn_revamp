import request from "@/utils/request";



export async function getLeaveType(id?: any) {
    return await request(`/api/leave-types/${id}`);
  }
  export async function getAllLeaveEntitlement(params?: any) {
    return await request('/api/leave-entitlement', {
      params,
    });
  }
  export async function getMyLeaveEntitlement(params?: any) {
    return await request('/api/my-leave-entitlement')
  }
  
  export async function getLeaveEntitlement(id: string) {
    return await request(`/api/leave-entitlement/${id}`, {}, true);
  }
  
  export async function addLeaveEntitlement(data: any) {
    return await request(
      '/api/leave-entitlement',
      {
        method: 'POST',
        data,
      },
      true,
    );
  }
  
  export async function updateLeaveEntitlement(id: string, data: any) {
    return await request(
      `/api/leave-entitlement/${id}`,
      {
        method: 'PUT',
        data,
      },
      true,
    );
  }
  
  export async function deleteLeaveEntitlement(id: string) {
    return await request(
      `/api/leave-entitlement/${id}`,
      {
        method: 'DELETE',
      },
      true,
    );
  }

  export async function addLeaveEntitlementForMultiple(data: any) {
    return await request(
      '/api/leave-entitlement-multiple',
      {
        method: 'POST',
        data,
      },
      true,
    );
  }
  export async function getExistingEmployees(id?: any) {
    return await request('/api/leave-entitlement/employees');
  }
  export async function getExistingLeaveTypes(id?: any) {
    return await request(`/api/leave-entitlement/leave-types`);
  }
  export async function getExistingLeavePeriods(params?: any) {
    return await request('/api/leave-entitlement/leave-periods', {
      params,
    });
  }
  
  export async function getLeaveTypes(params?: any) {
    return await request('/api/leave-types', {
      params,
    });
  }
