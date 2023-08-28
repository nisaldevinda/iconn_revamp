import React, { useState, useEffect } from 'react'
import { getModel, Models } from '@/services/model';
import BasicContainer from '@/components/BasicContainer';
import {
    queryPermissionData,
    addPermissionData,
    updatePermission,
    removePermission
} from '@/services/workflowServices'


const Permission: React.FC = () => {


    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.Permissions).then((model) => {
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
        defaultTitle="Permissions"
        model={model}
        tableColumns={[
          { name: 'roleId', sortable: true, filterable: true },
          { name: 'actionId', sortable: true, filterable: true },
        ]}
        recordActions={['add', 'edit', 'delete']}
        disableSearch = {true}
        addFormType="model"
        editFormType="drawer"
        isforceReload= {true}
        getAllFunction={queryPermissionData}
        addFunction={addPermissionData}
        editFunction={updatePermission}
        deleteFunction={removePermission}
        permissions={{
          addPermission: 'workflow-management-read-write',
          editPermission: 'workflow-management-read-write',
          deletePermission: 'workflow-management-read-write',
          readPermission: 'workflow-management-read-write',
        }}
      />
    );
}

export default Permission