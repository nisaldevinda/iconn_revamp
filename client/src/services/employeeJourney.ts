import request from '@/utils/request';

export async function createNewPromotion(employeeId: number, data: any) {
    return request(
        `api/employee-journey/${employeeId}/promotions`,
        { method: 'POST', data },
        true
    );
}

export async function contractRenewal(employeeId: number, data: any) {
    return request(
        `api/employee-journey/${employeeId}/confirmation-contracts`,
        { method: 'POST', data },
        true
    );
}

export async function createNewTransfer(employeeId: number, data: any) {
    return request(
        `api/employee-journey/${employeeId}/transfers`,
        { method: 'POST', data },
        true
    );
}

export async function createResignation(employeeId: number, data: any) {
    return request(
        `api/employee-journey/${employeeId}/resignations`,
        { method: 'POST', data },
        true
    );
}

export async function sendResignationRequest(data: any) {
    return request(
        `api/employee-journey/send-resignation-request`,
        { method: 'POST', data },
        true
    );
}

export async function updateCurrentJob(scope: string, employeeId: number, data: any) {
    return request(
        `api/employee-journey/${employeeId}/update-current-job?scope=${scope}`,
        { method: 'PUT', data },
        true
    );
}

export async function getAttachment(employeeId: number, fileId: number) {
    return await request(`api/employee-journey/${employeeId}/attachment/${fileId}`);
}

export async function getResignationAttachment(employeeId: number, fileId: number) {
    return await request(`api/employee-journey/resignation-attachment/${fileId}`);
}

export async function reupdateUpcomingEmployeeJourneyMilestone(employeeId: number, recordId: number, data: any) {
    return request(
        `api/employee-journey/${employeeId}/upcoming-milestone/${recordId}`,
        { method: 'PUT', data },
        true
    );
}

export async function rollbackUpcomingEmployeeJourneyMilestone(employeeId: number, recordId: number, data: any) {
    return request(
        `api/employee-journey/${employeeId}/upcoming-milestone/${recordId}`,
        { method: 'DELETE', data },
        true
    );
}

export async function getRejoinEligibleList() {
  return await request(`api/employee-journey/rehire-process/rejoin`);
}

export async function getReactiveEligibleList() {
  return await request(`api/employee-journey/rehire-process/reactive`);
}

export async function rejoinEmployee(data: any) {
  return request(
      `api/employee-journey/rehire-process/rejoin/${data.employeeId}`,
      { method: 'POST', data },
      true
  );
}

export async function reactiveEmployee(data: any) {
  return request(
      `api/employee-journey/rehire-process/reactive/${data.employeeId}`,
      { method: 'POST', data },
      true
  );
}
