import request from '@/utils/request';
import {  IWorkPatternModel } from '../pages/WorkPattern/data';

export function getWorkPattern(id: String) {
  return request(`/api/work-patterns/${id}`);
}

export function getWorkPatternEmployees(id: String) {
  return request(`/api/get-work-pattern-employees/${id}`);
}

export function createWorkPattern(data:  IWorkPatternModel) {
  return request('/api/work-patterns', {
    method: 'POST',
    data
  });
}

export function updateWorkPattern(id: String, data:  IWorkPatternModel) {
  return request(`/api/work-patterns/${id}`, {
    method: 'PUT',
    data
  });
}

export function getWorkPatterns(params?:any) {
  return request(`/api/work-patterns`, {
    params,
  });
}

export function getAllWorkPatterns(params?:any) {
  return request(`/api/list-work-patterns`, {
    params,
  });
}
export function createDuplicateWorkPattern(data: IWorkPatternModel) {
  return request(`/api/duplicate-work-patterns`, {
   method: 'POST',
      data
   });
 }



export function deleteWorkPattern(id: String) {
  return request(`/api/work-patterns/${id}`, {
    method: 'DELETE'
  });
}

export function deleteWeek(id: String, params?:any) {
  return request(`/api/delete-week/${id}`, {
    method: 'PUT',
    params
  });
}
export function assignWorkPattern(data: any) {
  return request('/api/assign-work-patterns', {
      method: 'POST',
      data
  });
}
