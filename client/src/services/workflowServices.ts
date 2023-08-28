import request from '@/utils/request';

export async function queryActionData(params: any) {
    return await request('/api/workflowAction', { params });
}
export async function queryContextBaseActionData(params: any) {
    return await request('/api/workflowContextBaseAction', { params });
}
export async function queryStateData(params: any) {
    return request('/api/workflowState', { params });
}
export async function queryPermissionData(params: any) {
    return request('/api/workflowPermission', { params });
}
export async function queryContextData(params: any) {
    return request('/api/workflowContext', { params });

}
export async function workflowEmployeeGroups(params: any) {
    return request('/api/workflowEmployeeGroups', { params });

}
export async function queryDefineData(params: any) {
    return request('/api/workflowDefine', { params });

}
export async function queryStateTransitionData(params: any) {
    return request('/api/workflowStateTransition', { params });

}

export async function getWorkflowConfigTree(workflowId: any) {
return request(`/api/workflow-config-tree/${workflowId}`);
}

export function addWorkflowEntity(params: any, workflowId: any) {
    return request(
        `api/workflow-builder/update-workflow-procedure-type/${workflowId}`,
      {
        method: 'PUT',
        data: { ...params },
      },
      true,
    );
}

export function changeWorkflowLevelConfigurations(params: any) {
    return request(
        `api/workflow-builder/update-workflow-level-configurations/${params.id}`,
      {
        method: 'PUT',
        data: { ...params },
      },
      true,
    );
}

export function addWorkflowApproverLevelEntity(params: any) {
    return request(
        `api/workflow-builder/add-workflow-approver-level`,
      {
        method: 'POST',
        data: { ...params },
      },
      true,
    );
}

export function deleteApprovalLevel(params: any) {
    return request(
      `api/workflow-builder/delete-workflow-approver-level/${params.id}`,
      {
        method: 'DELETE',
      },
      true,
    );
  }

export async function workflowApproverPools(params: any) {
    return request('/api/workflowApproverPools', { params });

}

export async function addWorkflowApproverPool(params: any) {
    return request('api/workflowApproverPools', {
        method: 'POST',
        data: { ...params }
    },
        true
    );
}

export async function updateWorkflowApproverPool(params: any) {
    return request(`api/workflowApproverPools/${params.id}`, {
        method: 'PUT',
        data: {
            ...params,
        },
    });
}
export async function removeWorkflowApproverPool(record: any) {
    return request(`api/workflowApproverPools/${record.id}`, {
        method: 'DELETE',
    },
        true
    );
}
//add data

export async function addActionData(params: any) {
    return request('api/workflowAction/', {
        method: 'POST',
        data: { ...params }
    },
        true
    );
}

export async function addStateData(params: any) {
    return request('api/workflowState/', {
        method: 'POST',
        data: { ...params }
    },
        true
    );
}

export async function addPermissionData(params: any) {
    return request('api/workflowPermission/', {
        method: 'POST',
        data: { ...params }
    },
        true
    );
}

export async function addContextData(params: any) {
    return request('api/workflowContext/', {
        method: 'POST',
        data: { ...params }
    },
        true
    );
}
export async function addDefineData(params: any) {
    return request('api/workflowDefine/', {
        method: 'POST',
        data: { ...params }
    },
        true
    );
}

export async function addStateTransitionData(params: any) {
    return request('api/workflowStateTransition/', {
        method: 'POST',
        data: { ...params }
    },
        true
    );
}
export async function addWorkflowEmployeeGroup(params: any) {
    return request('api/workflowEmployeeGroups/', {
        method: 'POST',
        data: { ...params }
    },
        true
    );
}



//update


export async function updateAction(params: any) {
    return request(`api/workflowAction/${params.id}`, {
        method: 'PUT',
        data: {
            ...params,
        },
    });
}
export async function updateState(params: any) {
    return request(`api/workflowState/${params.id}`, {
        method: 'PUT',
        data: {
            ...params,
        },
    });
}
export async function updatePermission(params: any) {
    return request(`api/workflowPermission/${params.id}`, {
        method: 'PUT',
        data: {
            ...params,
        },
    });
}
export async function updateContext(params: any) {
    return request(`api/workflowContext/${params.id}`, {
        method: 'PUT',
        data: {
            ...params,
        },
    });
}
export async function updateWorkflowEmployeeGroup(params: any) {
    return request(`api/workflowEmployeeGroups/${params.id}`, {
        method: 'PUT',
        data: {
            ...params,
        },
    });
}
export async function updateDefine(params: any) {
    return request(`api/workflowDefine/${params.id}`, {
        method: 'PUT',
        data: {
            ...params,
        },
    });
}

export async function updateStateTransition(params: any) {
    return request(`api/workflowStateTransition/${params.id}`, {
        method: 'PUT',
        data: {
            ...params,
        },
    });
}

//delete

export async function removeAction(record: any) {
    return request(`api/workflowAction/${record.id}`, {
        method: 'DELETE',
    },
        true
    );
}
export async function removeState(record: any) {
    return request(`api/workflowState/${record.id}`, {
        method: 'DELETE',
    },
        true
    );
}
export async function removePermission(record: any) {
    return request(`api/workflowPermission/${record.id}`, {
        method: 'DELETE',
    },
        true
    );
}
export async function removeContext(record: any) {
    return request(`api/workflowContext/${record.id}`, {
        method: 'DELETE',
    },
        true
    );
}
export async function removeWorkflowEmployeeGroup(record: any) {
    return request(`api/workflowEmployeeGroups/${record.id}`, {
        method: 'DELETE',
    },
        true
    );
}
export async function removeDefine(record: any) {
    return request(`api/workflowDefine/${record.id}`, {
        method: 'DELETE',
    },
        true
    );
}
export async function removeStateTransition(record: any) {
    return request(`api/workflowStateTransition/${record.id}`, {
        method: 'DELETE',
    },
        true
    );
}



export async function queryTransitionData(workFlowId: number, params: any) {

    return request(`/api/workflowStateTransition/${workFlowId}`);
}



export async function queryWorkflowInstancesById(userId: number, params: any) {
    return request(`/api/workflow/${userId}`, { params });
}

export async function getMyRequests(params: any) {
  return request(`/api/workflow`, { params });
}
export async function getLeaveCoveringRequests(params: any) {
  return request(`/api/leave-covering-requests`, { params });
}

export async function queryWorkflowInstances(userId: number, params: any) {

    return request(`/api/allWorkflow/${userId}`, { params });
}
export async function queryWorkflowFilterOptions() {

    return request(`/api/workflowFilter`);
}
export async function deleteInstance(id: number) {

    return request(`api/workflow/${id}`, {
        method: 'DELETE',
    });
}

export async function updateInstance(params) {

    return request(`api/workflow/${params.instanceId}`, {
        method: 'PUT',
        data: { ...params }
    });
}

export async function accessibleWorkflowActions(workflowId: number, employeeId: number, params: any, instanceId :any) {
    console.log(instanceId);
  return request(
    `/api/accessible-workflow-actions/${workflowId}/workflow/${employeeId}/employee/${instanceId}`,
    { params },
  );
}

export async function getWorkflows(params: any) {
    return request(`/api/workflowDefine`, { params });
}

export async function getWorkflowActions(params: any) {
    return request(`/api/workflowAction`, { params });
}

export async function getWorkflowStates(params: any) {
    return request(`/api/workflowState`, { params });
}

export async  function getPendingRequestCount() {
    return await request(`/api/employee-pending-request-count`);
}