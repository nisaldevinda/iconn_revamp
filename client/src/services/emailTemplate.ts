import request from '@/utils/request';
import { IDocumentTemplateModel } from '../pages/DocumentTemplate/data';

export function getEmailTemplate(id: String) {
  return request(`/api/email-templates/${id}`);
}

export function createEmailTemplate(data: IDocumentTemplateModel) {
  return request('/api/email-templates', {
    method: 'POST',
    data
  });
}

export function updateEmailTemplate(id: String, data: IDocumentTemplateModel) {
  return request(`/api/email-templates/${id}`, {
    method: 'PUT',
    data
  });
}

export function getEmailTemplates(params?:any) {
  return request(`/api/email-templates`, {
    params,
  });
}

export function getEmailTemplateContents(params?:any) {
  return request(`/api/email-template-contents`, {
    params,
  });
}

export function getEmailTemplateContentsByContextId(params?:any) {
  return request(`/api/email-template-contents-by-context-id`, {
    params,
  });
}
export function getEmailTemplateContent(id: String) {
  return request(`/api/email-templates-contents/${id}`);
}

export function getEmailNotificationTreeData() {
  return request(`/api/email-templates-tree-data`);
}

export function deleteEmailTemplate(id: String) {
  return request(`/api/email-templates/${id}`, {
    method: 'DELETE'
  });
}
export function getWorkflowContexts() {
  return request(`/api/workflowContext`);
}

