
import React, { useState, useEffect } from 'react'
import { getModel, Models } from '@/services/model';
import BasicContainer from '@/components/BasicContainer';

import {
    getAllCompetency,
    addCompetency,
    updateCompetency,
    removeCompetency
} from '@/services/competency'


const Competency: React.FC = () => {

    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.Competency).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })

    return (
      <BasicContainer
        rowId="id"
        titleKey="competency"
        defaultTitle="Competency"
        model={model}
        tableColumns={[
          { name: 'name', sortable: true, filterable: true },
          { name: 'competencyType', sortable: true, filterable: true },
        ]}
        recordActions={['add', 'edit', 'delete']}
        defaultSortableField={{ fildName: 'name', mode: 'ascend' }}
        searchFields={['name']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={getAllCompetency}
        addFunction={addCompetency}
        editFunction={updateCompetency}
        deleteFunction={removeCompetency}
        permissions={{
          addPermission: 'master-data-write',
          editPermission: 'master-data-write',
          deletePermission: 'master-data-write',
          readPermission: 'master-data-write',
        }}
      />
    );

}
export default Competency
