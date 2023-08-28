import request from '@/utils/request';
export function getAssignShifts(id: String) {
    return request(`/api/assign-shifts/${id}`);
}      


export function getUnAssignedEmployeeList(params?:any) {
    return request(`/api/unassigned-employees-list`, {
        params,
    });
}   

export function assignShifts(data: any) {
    return request('/api/assign-shifts', {
        method: 'POST',
        data
    });
}


     