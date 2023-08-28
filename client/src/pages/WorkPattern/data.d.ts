export interface IWorkPatternModel {
    id?: string;
    name: string;
    description?: string;
    createdAt?: Date;
    updatedAt?: Date;
    createdBy?: number;
    updatedBy?: number;
  };
  

export interface IWorkPatternForm  {
    name: string;
    description?: string;
    countryId : Array ;
    locationId : Array ;
  };
  export interface IParams {
    id: string
  }
  
  export interface IWorkPatternListItem {
    id: string;
    name: string;
    description?: string;
    createdAt?: string;
  }
  
  