import { getModels, setModels } from '@/utils/model';
import request from '@/utils/request';
import _ from 'lodash';

export enum Models {
  User = 'user',
  Employee = 'employee',
  // Department = 'department',
  // Division = 'division',
  EmploymentStatus = 'employmentStatus',
  Gender = 'gender',
  JobTitle = 'jobTitle',
  Location = 'location',
  MaritalStatus = 'maritalStatus',
  Nationality = 'nationality',
  Qulification = 'qualification',
  QualificationInstitutions = 'qualificationInstitution',
  QualificationLevel = 'qualificationLevel',
  Race = 'race',
  Relationship = 'relationship',
  Religion = 'religion',
  TerminationReason = 'terminationReason',
  CompetencyType = 'competencyType',
  Competency = 'competency',
  UserRole = 'userRole',
  Notice = 'notice',
  Dashboard = 'dashboard',
  SalaryComponents = 'salaryComponents',
  PayGrades = 'payGrades',
  Actions = 'workflowAction',
  States = 'workflowState',
  Permissions = 'workflowPermission',
  Contexts = 'workflowContext',
  Defines = 'workflowDefine',
  WorkflowTransitions = 'workflowStateTransition',
  ReportData = 'reportData',
  Company = 'company',
  LeaveEntitlement = 'leaveEntitlement',
  LeaveAccrual = 'leaveAccrual',
  DocumentTemplate = 'documentTemplate',
  DynamicForm = 'dynamicModel',
  NoticePeriodConfig = 'noticePeriodConfig',
  Scheme = 'scheme',
  PromotionType = 'promotionType',
  ConfirmationReason = 'confirmationReason',
  TransferType = 'transferType',
  ResignationType = 'resignationType',
  EmployeeJob = 'employeeJob',
  ResignationProcess = 'resignationProcess',
  employeeNumberConfiguration = 'employeeNumberConfiguration',
  ClaimCategory = 'claimCategory',
}

export type ModelType = {
  modelDataDefinition: any;
  frontEndDefinition: any;
};

export async function getModel(model: Models | String, alternative?: string) {
  let models = await getModels();
  if (!models) models = {};

  let modelObject = alternative ? models[model]?.[alternative] : models[model];

  if (modelObject) {
    if (!modelObject.modelDataDefinition && !modelObject.frontEndDefinition)
      modelObject = Object.values(modelObject)[0];

    return Promise.resolve({
      data: modelObject,
      message: 'Model loaded successfully.',
      statusCode: 200
    });
  } else {
    let response = await request(alternative
      ? `/api/models/${model}/${alternative}`
      : `/api/models/${model}`);

    if (alternative) {
      if (!models[model]) models[model] = {};
      models[model][alternative] = response.data;
    } else {
      models[model] = response.data;
    }

    setModels(models);
    return response;
  }
}

export async function getAllModel() {
  return await request('/api/models');
}

export async function getTemplateTokens() {
  return request(`/api/models/template-tokens`);
}

export async function getWorkflowTemplateTokens(params: any) {
  return request(`/api/models/workflow-template-tokens`, { params }, true);
}
