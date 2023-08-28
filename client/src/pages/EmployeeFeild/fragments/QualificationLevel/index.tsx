import React, { useState, useEffect } from 'react'
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';
import {
    getAllQualificationLevels,
    addQualificationLevel,
    updateQualificationLevel,
    removeQualificationLevel
} from '@/services/qualificationLevel'


const QualificationLevel: React.FC = () => {

    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.QualificationLevel).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })



    return (
      <BasicContainer
        rowId="id"
        titleKey="qualification-level"
        defaultTitle="Qualification Levels"
        model={model}
        tableColumns={[{ name: 'name', sortable: true, filterable: true }]}
        recordActions={['add', 'edit', 'delete']}
        defaultSortableField={{ fildName: 'name', mode: 'ascend' }}
        searchFields={['name']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={getAllQualificationLevels}
        addFunction={addQualificationLevel}
        editFunction={updateQualificationLevel}
        deleteFunction={removeQualificationLevel}
        permissions={{
          addPermission: 'master-data-write',
          editPermission: 'master-data-write',
          deletePermission: 'master-data-write',
          readPermission: 'master-data-write',
        }}
      />
    );
}

export default QualificationLevel