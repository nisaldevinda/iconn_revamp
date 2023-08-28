import React, { useState, useEffect } from 'react'
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';
import {
    getAllReligion,
    addReligion,
    updateReligion,
    removeReligion
} from '@/services/religion'


const Religions: React.FC = () => {

    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.Religion).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })

    return (
      <BasicContainer
        rowId="id"
        titleKey="religions"
        defaultTitle="Religions"
        model={model}
        tableColumns={[{ name: 'name', sortable: true, filterable: true }]}
        recordActions={['add', 'edit', 'delete']}
        defaultSortableField={{ fildName: 'name', mode: 'ascend' }}
        searchFields={['name']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={getAllReligion}
        addFunction={addReligion}
        editFunction={updateReligion}
        deleteFunction={removeReligion}
        permissions={{
          addPermission: 'master-data-write',
          editPermission: 'master-data-write',
          deletePermission: 'master-data-write',
          readPermission: 'master-data-write',
        }}
      />
    );
}

export default Religions
