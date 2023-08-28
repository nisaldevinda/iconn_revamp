
import React, { useState, useEffect } from 'react'
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';
import {
    getAllRace,
    addRace,
    updateRace,
    removeRace
} from '@/services/race'



const Race: React.FC = () => {

    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.Race).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })

    return (
      <BasicContainer
        rowId="id"
        titleKey="race"
        defaultTitle="Race"
        model={model}
        tableColumns={[{ name: 'name', sortable: true, filterable: true }]}
        recordActions={['add', 'edit', 'delete']}
        defaultSortableField={{ fildName: 'name', mode: 'ascend' }}
        searchFields={['name']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={getAllRace}
        addFunction={addRace}
        editFunction={updateRace}
        deleteFunction={removeRace}
        permissions={{
          addPermission: 'master-data-write',
          editPermission: 'master-data-write',
          deletePermission: 'master-data-write',
          readPermission: 'master-data-write',
        }}
      />
    );
}

export default Race