export type DepartmentGetDataParams = {
    id: number;
    name: string;
    headOfDepartment: number;
    parentDepartment: number;
  };
  export interface DrawerProps {
    isVisible: boolean;
    data: []
  }