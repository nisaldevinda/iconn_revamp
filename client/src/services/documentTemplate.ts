import request from '@/utils/request';
import { IDocumentTemplateModel } from '../pages/DocumentTemplate/data';

export function getDocumentTemplate(id: String) {
  return request(`/api/document-templates/${id}`);
}

export function createDocumentTemplate(data: IDocumentTemplateModel) {
  return request('/api/document-templates', {
    method: 'POST',
    data
  });
}

export function updateDocumentTemplate(id: String, data: IDocumentTemplateModel) {
  return request(`/api/document-templates/${id}`, {
    method: 'PUT',
    data
  });
}

export function getDocumentTemplates(params?:any) {
  return request(`/api/document-templates`, {
    params,
  });
}

export function deleteDocumentTemplate(record: any) {
  return request(
    `/api/document-templates/${record.id}`,
    {
      method: 'DELETE',
    },
    true
  );
}

export function getEmployeeDocument(employeeId: String, templateId: String) {
  return request(`/api/employees/${employeeId}/document-templates/${templateId}`);
}

export function downloadPdf(employeeId: String, templateId: String, content: String) {
  return request(`api/employees/${employeeId}/document-templates/${templateId}/download-pdf`, {
    method: 'POST',
    data: {
      content
    },
  });
}

export function downloadDocx(employeeId: String, templateId: String, content: String) {
  return request(`api/employees/${employeeId}/document-templates/${templateId}/download-docx`, {
    method: 'POST',
    data: {
      content
    }
  });
}

export function createDocumentCategory(data:any) {
  return request('/api/document-templates/category', {
    method: 'POST',
    data
  });
}

export async function getDocumentCategories() {
  return await request('/api/document-templates-categories');
}

export function getDocumentsList(params?:any) {
  return request(`/api/document-templates-list/${params}`);
}

export function addBulkLetter( data:any) {
  return request ('/api/document-templates/bulk-letter',{
    method: 'POST',
    data
  })
}
