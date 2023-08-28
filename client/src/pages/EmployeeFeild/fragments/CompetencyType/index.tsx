
import React, { useState, useEffect } from 'react'
import { getModel, Models } from '@/services/model';
import BasicContainer from '@/components/BasicContainer';

import {
    getAllCompetencyTypes,
    addCompetencyTypes,
    updateCompetencyTypes,
    removeCompetencyTypes
} from '@/services/competencyTypes'


const CompetencyType: React.FC = () => {

    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.CompetencyType).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })

    return (
      <BasicContainer
        rowId="id"
        titleKey="competency-type"
        defaultTitle="Competency Type"
        model={model}
        tableColumns={[{ name: 'name', sortable: true, filterable: true }]}
        recordActions={['add', 'edit', 'delete']}
        defaultSortableField={{ fildName: 'name', mode: 'ascend' }}
        searchFields={['name']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={getAllCompetencyTypes}
        addFunction={addCompetencyTypes}
        editFunction={updateCompetencyTypes}
        deleteFunction={removeCompetencyTypes}
        permissions={{
          addPermission: 'master-data-write',
          editPermission: 'master-data-write',
          deletePermission: 'master-data-write',
          readPermission: 'master-data-write',
        }}
      />
    );

}
export default CompetencyType
