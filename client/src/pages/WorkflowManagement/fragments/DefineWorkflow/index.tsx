import React, { useState, useEffect } from 'react'
import { getModel, Models } from '@/services/model';
import BasicContainer from '@/components/BasicContainer';
import {
    queryDefineData,
    addDefineData,
    updateDefine,
    removeDefine
} from '@/services/workflowServices'


const Define: React.FC = () => {


    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.Defines).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })


    return (
      <BasicContainer
        rowId="id"
        titleKey="defines"
        defaultTitle="Workflow"
        model={model}
        tableColumns={[
          { name: 'workflowName', sortable: true, filterable: true },
          { name: 'description', sortable: true, filterable: true },
          { name: 'context', sortable: true, filterable: true },
          { name: 'employeeGroup', sortable: false, filterable: false },
        ]}
        recordActions={['add', 'edit', 'delete', 'view']}
        searchFields={['workflowName']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={queryDefineData}
        addFunction={addDefineData}
        editFunction={updateDefine}
        deleteFunction={removeDefine}
        permissions={{
          addPermission: 'workflow-management-read-write',
          editPermission: 'workflow-management-read-write',
          deletePermission: 'workflow-management-read-write',
          readPermission: 'workflow-management-read-write',
        }}
      />
    );
}

export default Define