import request from '@/utils/request';

    export function getWorkShifts(id: String) {
        return request(`/api/work-shifts/${id}`);
      }
      
      export function createWorkShifts(data: any) {
        return request('/api/work-shifts', {
          method: 'POST',
          data
        });
      }
      
      export function updateWorkShifts(id: String, data:any) {
        return request(`/api/work-shifts/${id}`, {
          method: 'PUT',
          data
        });
      }
      
      
      
      export function getAllWorkShifts(params?:any) {
        return request(`/api/list-work-shifts`, {
          params,
        });
      }
      export function deleteWorkShifts(id: String) {
        return request(`/api/work-shifts/${id}`, {
          method: 'DELETE'
        });
      }
      
      export function getWorkShiftDayType(params?:any) {
        return request(`/api/work-shift-day-type`, {
          params,
        });
      }
 
      export async function getWorkShiftsList() {
        return await request('/api/work-shifts-list');
      }
      
      
      export function createAdhocWorkShifts(data: any) {
        return request('/api/adhoc-work-shifts', {
          method: 'POST',
          data
        });
      }