import request from '@/utils/request';


export async function claimCategories(params: any) {
    return request('/api/expense-management/claimCategories', { params });

}

export async function addClaimCategory(params: any) {
    return request('api/expense-management/claimCategories', {
        method: 'POST',
        data: { ...params }
    },
        true
    );
}

export async function updateClaimCategory(params: any) {
    return request(`api/expense-management/claimCategories/${params.id}`, {
        method: 'PUT',
        data: {
            ...params,
        },
        
    }, true);
}
export async function removeClaimCategory(record: any) {
return request(`api/expense-management/claimCategories/${record.id}`, {
        method: 'DELETE',
    },
        true
    );
}

export async function claimTypes(params: any) {
    return request('/api/expense-management/claimTypes', { params });

}

export async function getEmployeeEligibleClaimTypes(params: any) {
    return request('/api/expense-management/get-employee-eligible-claim-types', { params });

}

export async function getClaimAllocationDetails(params: any) {
    return request('/api/expense-management/get-employee-claim-allocation-data', { params });

}

export async function getAllocationEnableClaimTypes(params: any) {
    return request('/api/expense-management/get-allocation-enable-claim-types', { params });
}

export async function getClaimAllocationData(params?:any) {
    return await request('/api/expense-management/get-employee-claim-alocation-list', {
      params,
    });
}

export async function addEmployeeClaimAllocation(params: any) {
    return request('api/expense-management/add-employee-claim-alocation', {
        method: 'POST',
        data: { ...params }
    },
        true
    );
}
export async function addBulkEmployeeClaimAllocation(params: any) {
    return request('api/expense-management/add-bulk-employee-claim-alocation', {
        method: 'POST',
        data: { ...params }
    },
        true
    );
}

export async function updateEmployeeClaimAllocations(data:any) {
    return await request(
      '/api/expense-management/update-employee-claim-allocations', {
        method: 'POST',
        data
      },
      true
    );
  }

export async function addClaimType(params: any) {
    return request('api/expense-management/claimTypes', {
        method: 'POST',
        data: { ...params }
    },
        true
    );
}

export async function updateClaimType(params: any) {
    return request(`api/expense-management/claimTypes/${params.id}`, {
        method: 'PUT',
        data: {
            ...params,
        },
        
    }, true);
}
export async function removeClaimType(record: any) {
    return request(`api/expense-management/claimTypes/${record.id}`, {
        method: 'DELETE',
    },
        true
    );
}

export async function removeEmployeeClaimAllocation(record: any) {
    return request(`api/expense-management/claimAllocation/${record.id}`, {
        method: 'DELETE',
    },
        true
    );
}


export async function claimPackages(params: any) {
    return request('/api/expense-management/claimPackages', { params });

}

export async function getClaimTypesByEntityId(params: any) {
    return request('/api/expense-management/getClaimTypesByEntityId', { params });

}

export async function addClaimPackage(params: any) {
    return request('api/expense-management/claimPackages', {
        method: 'POST',
        data: { ...params }
    },
        true
    );
}

export async function updateClaimPackage(params: any) {
    return request(`api/expense-management/claimPackages/${params.id}`, {
        method: 'PUT',
        data: {
            ...params,
        },
        
    }, true);
}
export async function removeClaimPackage(record: any) {
    return request(`api/expense-management/claimPackages/${record.id}`, {
        method: 'DELETE',
    },
        true
    );
}

export async function createEmployeeClaimRequest(params: any) {
    return request('api/expense-management/create-employee-claim-request', {
        method: 'POST',
        data: { ...params }
    },
        true
    );
}

export async function getReceiptAttachment(id: number) {
    return await request(`api/expense-management/receipt-attachment/${id}`);
}
