import request from '@/utils/request';


export async function addWorkShifts(params: any) {
  return request('api/workSchedules', {
    method: 'POST',
    data:{...params}
  },
    true
  );
}

export function getWorkShedules(params?:any) {
  return request(`/api/workSchedules`, {
    params,
  });
}

export function getEmployeeWorkPattern(params?:any) {
  return request(`/api/employee-work-pattern`, {
    params,
  });
}


export async function addEmployeeWorkPattern(params: any) {
  return request('api/employee-work-pattern', {
    method: 'POST',
    data:{...params}
  },
    true
  );
}


export async function saveShiftChangeRequest(params: any) {
  return request('api/save-shift-change-request', {
    method: 'POST',
    data:{...params}
  },
    true
  );
}

export async function getWorkShifts(params?: any) {
  return request('api/workSchedules/workShifts', { params });
}

export async function getWorkShiftsForShiftChange(params?: any) {
  return request('api/workSchedules/get-work-shifts-for-shift-change', { params });
}


export async function getWorkShiftById(id: String) {
  return request(`api/workSchedules/workShifts/${id}`);
}

export function getMyWorkSchedule(params?:any) {
  return request(`/api/my-work-schedule`, {
    params,
  });
}


export function getEmployeeWorkSchedule(params?:any) {
  return request(`/api/employee-work-schedule`, {
    params,
  });
}

export function getDateWiseShiftData(params?:any) {
  return request(`/api/get-date-wise-employee-work-shift`, {
    params,
  });
}

export function getWorkShedulesManagerView(params?:any) {
  return request(`/api/work-schedules-manager-view`, {
    params,
  });
}

export default {
  getMyWorkSchedule,
  getEmployeeWorkSchedule,
  addWorkShifts,
  getWorkShedules,
  getEmployeeWorkPattern,
  addEmployeeWorkPattern,
  getWorkShifts,
  getWorkShiftById,
  getDateWiseShiftData,
  getWorkShiftsForShiftChange,
  saveShiftChangeRequest
  
};

