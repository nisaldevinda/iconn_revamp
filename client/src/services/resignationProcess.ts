import request from '@/utils/request';
import { IDocumentTemplateModel } from '../pages/DocumentTemplate/data';

export function getResignationProcess(id: String) {
  return request(`/api/resignation-processes/${id}`);
}

export function createResignationProcess(data: IDocumentTemplateModel) {
  return request(
    '/api/resignation-processes',
    {
      method: 'POST',
      data,
    },
    true,
  );
}

export function updateResignationProcess(id: String, data: IDocumentTemplateModel) {
  return request(
    `/api/resignation-processes/${id}`,
    {
      method: 'PUT',
      data,
    },
    true,
  );
}

export function getResignationProcessList(params?: any) {
  return request(
    `/api/resignation-processes`,
    {
      params,
    },
    true,
  );
}

export function deleteResignationProcess(record: any) {
  return request(
    `/api/resignation-processes/${record.id}`,
    {
      method: 'DELETE',
    },
    true,
  );
}
