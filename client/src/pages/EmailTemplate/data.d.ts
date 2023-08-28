export interface IDocumentTemplateModel {
  id?: string;
  name: string;
  description?: string;
  content?: string;
  pageSettings?: IPageSettings;
  createdAt?: Date;
  updatedAt?: Date;
  createdBy?: number;
  updatedBy?: number;
};

export interface IPageSettings {
  pageSize: string;
  marginLeft: number;
  marginRight: number;
  marginTop: number;
  marginBottom: number;
}

export interface IDocumentTemplateForm extends IPageSettings {
  name: string;
  description?: string;
};

export interface IEditorContainerProps {
  loading: boolean;
  editorInit: any;
}

export interface ITokenOption {
  value: string;
  text: string;
}

export interface IParams {
  id: string
}

export interface IDocumentTemplateListItem {
  id: string;
  name: string;
  description?: string;
}
