

import React, { useState, useEffect } from 'react'
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';
import {
    getAllResignationTypes,
    addResignationTypes,
    updateResignationTypes,
    removeResignationTypes
} from '@/services/resignationTypes'

const ResignationTypes: React.FC = () => {

    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.ResignationType).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })
    return (
      <BasicContainer
        rowId="id"
        titleKey="resignation-types"
        defaultTitle="Resignation Types"
        model={model}
        tableColumns={[{ name: 'name', sortable: true, filterable: true }]}
        recordActions={['add', 'edit', 'delete']}
        defaultSortableField={{ fildName: 'name', mode: 'ascend' }}
        searchFields={['name']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={getAllResignationTypes}
        addFunction={addResignationTypes}
        editFunction={updateResignationTypes}
        deleteFunction={removeResignationTypes}
        permissions={{
          addPermission: 'master-data-write',
          editPermission: 'master-data-write',
          deletePermission: 'master-data-write',
          readPermission: 'master-data-write',
        }}
      />
    );
}

export default ResignationTypes