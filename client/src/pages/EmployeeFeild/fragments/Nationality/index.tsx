import React, { useState, useEffect } from 'react'
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';
import { getAllNationalities, addNationality, updateNationality, removeNationality } from '@/services/nationality'


const Nationality: React.FC = () => {

    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.Nationality).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })

    return (
      <BasicContainer
        rowId="id"
        titleKey="nationality"
        defaultTitle="Nationality"
        model={model}
        tableColumns={[{ name: 'name', sortable: true, filterable: true }]}
        recordActions={['add', 'edit', 'delete']}
        defaultSortableField={{ fildName: 'name', mode: 'ascend' }}
        searchFields={['name']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={getAllNationalities}
        addFunction={addNationality}
        editFunction={updateNationality}
        deleteFunction={removeNationality}
        permissions={{
          addPermission: 'master-data-write',
          editPermission: 'master-data-write',
          deletePermission: 'master-data-write',
          readPermission: 'master-data-write',
        }}
      />
    );
}

export default Nationality