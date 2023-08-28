import React, { useState, useEffect } from 'react';
import { getAllEmployeeNumberConfigs, addEmployeeNumberConfigs, updateEmployeeNumberConfigs, removeEmployeeNumberConfigs } from '@/services/employeeNumberConfiguration';
import { getModel, Models } from '@/services/model';
import BasicContainer from '@/components/BasicContainer';

const EmployeeNumberConfiguration: React.FC = () => {
  const [model, setModel] = useState<any>();
  useEffect(() => {
    if (!model) {
      getModel(Models.employeeNumberConfiguration).then((model) => {
        if (model && model.data) {
          setModel(model.data);
        }
      });
    }
  });

  return (
    <BasicContainer
      rowId="id"
      titleKey="employeeNumberConfiguration"
      defaultTitle="Employee Number Configuration"
      model={model}
      tableColumns={[
        { name: 'entity', sortable: true, filterable: true },
        { name: 'prefix', sortable: true, filterable: true },
        { name: 'numberLength' },
        { name: 'nextNumber' },
      ]}
      recordActions={['add', 'edit', 'delete']}
      defaultSortableField={{ fildName: 'prefix', mode: 'ascend' }}
      searchFields={['prefix']}
      addFormType="model"
      editFormType="drawer"
      getAllFunction={getAllEmployeeNumberConfigs}
      addFunction={addEmployeeNumberConfigs}
      editFunction={updateEmployeeNumberConfigs}
      deleteFunction={removeEmployeeNumberConfigs}
      permissions={{
        addPermission: 'master-data-write',
        editPermission: 'master-data-write',
        deletePermission: 'master-data-write',
        readPermission: 'master-data-write',
      }}
      fieldPermission={['*']}
    />
  );
};

export default EmployeeNumberConfiguration;
