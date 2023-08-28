import React, { useState, useEffect } from 'react'
import { getAllDivisions, addDivision, updateDivision, removeDivision } from '@/services/divsion'
import { getModel, Models } from '@/services/model';
import BasicContainer from '@/components/BasicContainer';

const Division: React.FC = () => {

    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.Division).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })

    return (
      <BasicContainer
        rowId="id"
        titleKey="division"
        defaultTitle="Division"
        model={model}
        tableColumns={[{ name: 'name', sortable: true, filterable: true }]}
        recordActions={['add', 'edit', 'delete']}
        defaultSortableField={{ fildName: 'name', mode: 'ascend' }}
        searchFields={['name']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={getAllDivisions}
        addFunction={addDivision}
        editFunction={updateDivision}
        deleteFunction={removeDivision}
        permissions={{
          addPermission: 'master-data-write',
          editPermission: 'master-data-write',
          deletePermission: 'master-data-write',
          readPermission: 'master-data-write',
        }}
      />
    );

}

export default Division