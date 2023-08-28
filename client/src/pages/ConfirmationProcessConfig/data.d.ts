export interface IResignationProcessConfigModel {
  id?: string;
  name: string;
  jobCategoryIds: Array;
  orgEntityId: number;
  formTemplateId: number;
  createdAt?: Date;
  updatedAt?: Date;
  createdBy?: number;
  updatedBy?: number;
};

