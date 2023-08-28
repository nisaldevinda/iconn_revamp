import request from '@/utils/request';

export async function getAttendance(params?:any) {
  return await request('/api/attendance', {
    params,
  },
  true
)}

export async function manageAttendance(data:any) {
  return await request(
    '/api/attendance', {
      method: 'POST',
      data
    },
    true
  );
}

export async function manageBreak(data:any) {
  return await request(
    '/api/attendance/break', {
      method: 'POST',
      data
    },
    true
  );
}

export async function  getAttendanceReportsData(params:any) {
  return await request('/api/attendanceSheet/getAttendanceReportData', {
    params,
  });
}

export async function getEmployeeData(params?:any) {
  return await request('/api/attendanceSheet/employees', {
    params,
  });
}

export async function getAttendanceSheetManagerData(params?:any) {
  return await request('/api/attendanceSheet/managerData', {
    params,
  });
}

export async function getAttendanceSheetEmployeeData(params?:any) {
  return await request('/api/attendanceSheet/employeeData', {
    params,
  });
}

export async function getAttendanceSheetForEmployeePostOtRequest(params?:any) {
  return await request('/api/attendanceSheet/getPostOtRequestAttendance', {
    params,
  });
}

export async function getRelatedBreakes(params?:any) {
  return await request('/api/attendanceSheet/getRelatedBreakes', {
    params,
  });
}
export async function checkOtAccessability(params?:any) {
  return await request('/api/attendanceSheet/checkOtAccessability', {
    params,
  });
}
export async function checkOtAccessabilityForCompany(params?:any) {
  return await request('/api/attendanceSheet/checkOtAccessabilityForCompany', {
    params,
  });
}

export async function getOthersAttendanceSummaryData(params?:any) {
  return await request('/api/attendanceSheet/othersSummary', {
    params,
  });
}

export async function getAttendanceSummaryData(params?:any) {
  return await request('/api/attendanceSheet/summary', {
    params,
  });
}

export async function getAttendanceSheetAdminData(params?:any) {
  return await request('/api/attendanceSheet/adminData', {
    params,
  });
}

export async function getInvalidAttendanceSheetAdminData(params?:any) {
  return await request('/api/attendanceSheet/invalid-attendance', {
    params,
  });
}

export async function requestTimeChange(data:any) {
  return await request(
    '/api/attendanceSheet/timeChange', {
      method: 'POST',
      data
    },
    true
  );
}

export async function requestUpdateBreaks(data:any) {
  return await request(
    '/api/attendanceSheet/updateBreaks', {
      method: 'POST',
      data
    },
    true
  );
}

export async function getAttendanceTimeChangeData(params?:any) {
  return await request('/api/attendanceSheet/requestTimeData', {
    params,
  });
}

export async function approveTimeChange(data:any) {
  return await request(
    '/api/attendanceSheet/approveTime', {
      method: 'POST',
      data
    },
    true
  );
}

export async function approveTimeChangeAdmin(data:any) {
  return await request(
    '/api/attendanceSheet/timeChangeAdmin', {
      method: 'POST',
      data
    },
    true
  );
}

export async function updateInvalidAttendences(data:any) {
  return await request(
    '/api/attendanceSheet/updateInvalidAttendance', {
      method: 'POST',
      data
    },
    true
  );
}

export async function createPostOtRequest(data:any) {
  return await request(
    '/api/attendanceSheet/createPostOtRequest', {
      method: 'POST',
      data
    },
    true
  );
}

export async function getLastLogged(params?: any) {
  return await request('/api/attendance/getLastLogged', {
    params,
  });
}

export async function accessibleWorkflowActions(workflowId: number, employeeId: number, params: any, instanceId: any) {
  return request(
    `/api/accessible-workflow-actions/${workflowId}/workflow/${employeeId}/employee/${instanceId}`,
    { params },
  );
}

export async function updateInstance(params: any) {

  return request(`api/workflow/${params.instanceId}`, {
      method: 'PUT',
      data: { ...params }
  });
}

export async function downloadManagerAttendanceView(params?:any) {
  return await request('/api/attendanceSheet/downloadManagerAttendanceView', {
    params,
  });
}

export async function downloadAdminAttendanceView(params?:any) {
  return await request('/api/attendanceSheet/downloadAdminAttendanceView', {
    params,
  });
}