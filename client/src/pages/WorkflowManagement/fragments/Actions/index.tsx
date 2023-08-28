import React, { useState, useEffect } from 'react'
import { getModel, Models } from '@/services/model';
import BasicContainer from '@/components/BasicContainer';
import {
    queryActionData,
    addActionData,
    updateAction,
    removeAction
} from '@/services/workflowServices'


const Action: React.FC = () => {


    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.Actions).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })


    return (
      <BasicContainer
        rowId="id"
        titleKey="action"
        defaultTitle="Actions"
        model={model}
        tableColumns={[
          { name: 'actionName', sortable: true, filterable: true },
          { name: 'label', sortable: true, filterable: true },
          { name: 'description', sortable: true, filterable: true },
        ]}
        recordActions={['add', 'edit', 'delete']}
        searchFields={['actionName']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={queryActionData}
        addFunction={addActionData}
        editFunction={updateAction}
        deleteFunction={removeAction}
        permissions={{
          addPermission: 'workflow-management-read-write',
          editPermission: 'workflow-management-read-write',
          deletePermission: 'workflow-management-read-write',
          readPermission: 'workflow-management-read-write',
        }}
      />
    );
}

export default Action