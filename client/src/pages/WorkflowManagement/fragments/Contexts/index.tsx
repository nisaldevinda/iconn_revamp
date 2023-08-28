import React, { useState, useEffect } from 'react'
import { getModel, Models } from '@/services/model';
import BasicContainer from '@/components/BasicContainer';
import {
    queryContextData,
    addContextData,
    updateContext,
    removeContext
} from '@/services/workflowServices'


const Context: React.FC = () => {


    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.Contexts).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })


    return (
      <BasicContainer
        rowId="id"
        titleKey="contexts"
        defaultTitle="Contexts"
        model={model}
        tableColumns={[{ name: 'contextName', sortable: true, filterable: true }]}
        recordActions={['view']}
        searchFields={['contextName']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={queryContextData}
        addFunction={addContextData}
        editFunction={updateContext}
        deleteFunction={removeContext}
        permissions={{
          addPermission: 'workflow-management-read-write',
          editPermission: 'workflow-management-read-write',
          deletePermission: 'workflow-management-read-write',
          readPermission: 'workflow-management-read-write',
        }}
      />
    );
}

export default Context