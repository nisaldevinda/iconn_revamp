import React, { useState, useEffect } from 'react'
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';
import { getAllMaritalStatus, addMaritalStatus, updateMaritalStatus, removeMaritalStatus } from '@/services/maritalStatus'



const MaritalStatus: React.FC = () => {

    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.MaritalStatus).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })

    return (
      <BasicContainer
        rowId="id"
        titleKey="marital-status"
        defaultTitle="Marital Status"
        model={model}
        tableColumns={[{ name: 'name', sortable: true, filterable: true }]}
        recordActions={['add', 'edit', 'delete']}
        defaultSortableField={{ fildName: 'name', mode: 'ascend' }}
        searchFields={['name']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={getAllMaritalStatus}
        addFunction={addMaritalStatus}
        editFunction={updateMaritalStatus}
        deleteFunction={removeMaritalStatus}
        permissions={{
          addPermission: 'master-data-write',
          editPermission: 'master-data-write',
          deletePermission: 'master-data-write',
          readPermission: 'master-data-write',
        }}
      />
    );
}

export default MaritalStatus