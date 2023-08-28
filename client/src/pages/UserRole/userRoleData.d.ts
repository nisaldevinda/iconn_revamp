export type TableListItem = {
    id: number;
    title: string;
    type:number;
    isIndirectAccess:Boolean;
    customCriteria: any;
    permittedActions: any;
    readableFields: any;
    editableFields:any;
    createdBy?: string;
    updatedBy?: string;
    createdAt?: string;
    updatedAt?: string;
  
  
  };
  
  export type AuditLogTableParams = {
    id?: number;
    name?: string;
    modelName: string;
    modelId:number;
    employeeId:number;
    action: string;
    isDelete?: string;
    pageSize?: number;
    currentPage?: number;
    filter?: Record<string, any[]>;
    sorter?: Record<string, any>;
  };
  
  export type ModelVisibilityTypes = {
    add: boolean;
    edit: boolean;
    delete: boolean;
    
  };
  
  export type ConfirmModelLoadingTypes = {
    add: boolean;
    edit: boolean;
    delete: boolean;
  };

  export type FieldData = {
    key: string;
    value: string; 
  };

  export type SectionData = {
    label: string;
    fields: FieldData[];
  };

  export type TabData = {
    label: string;
    sections: SectionData[];
  };

  export type FieldPermission = {
    key: string;
    label: string;
    section: string;
    tab: string;
    fieldType: string;
    permission: string;
  };
  