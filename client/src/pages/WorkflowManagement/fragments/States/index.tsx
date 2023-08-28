import React, { useState, useEffect } from 'react'
import { getModel, Models } from '@/services/model';
import BasicContainer from '@/components/BasicContainer';
import {
    queryStateData,
    addStateData,
    updateState,
    removeState
} from '@/services/workflowServices'


const State: React.FC = () => {


    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.States).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })


    return (
      <BasicContainer
        rowId="id"
        titleKey="states"
        defaultTitle="States"
        model={model}
        tableColumns={[
          { name: 'stateName', sortable: true, filterable: true },
          { name: 'label', sortable: true, filterable: true },
          { name: 'description', sortable: true, filterable: true },
        ]}
        recordActions={['add', 'edit', 'delete', 'view']}
        searchFields={['stateName']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={queryStateData}
        addFunction={addStateData}
        editFunction={updateState}
        deleteFunction={removeState}
        permissions={{
          addPermission: 'workflow-management-read-write',
          editPermission: 'workflow-management-read-write',
          deletePermission: 'workflow-management-read-write',
          readPermission: 'workflow-management-read-write',
        }}
      />
    );
}

export default State