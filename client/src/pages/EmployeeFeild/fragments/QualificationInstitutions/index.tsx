

import React, { useState, useEffect } from 'react'
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';
import {
    getAllQualificationInstitutions,
    addQualificationInstitutions,
    updateQualificationInstitutions,
    removeQualificationInstitutions
} from '@/services/qualificationInstitution'



const QualificationInstitutions: React.FC = () => {

    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.QualificationInstitutions).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })


    return (
      <BasicContainer
        rowId="id"
        titleKey="qualification-institutions"
        defaultTitle="Qualification Institutions"
        model={model}
        tableColumns={[{ name: 'name', sortable: true, filterable: true }]}
        recordActions={['add', 'edit', 'delete']}
        defaultSortableField={{ fildName: 'name', mode: 'ascend' }}
        searchFields={['name']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={getAllQualificationInstitutions}
        addFunction={addQualificationInstitutions}
        editFunction={updateQualificationInstitutions}
        deleteFunction={removeQualificationInstitutions}
        permissions={{
          addPermission: 'master-data-write',
          editPermission: 'master-data-write',
          deletePermission: 'master-data-write',
          readPermission: 'master-data-write',
        }}
      />
    );
}

export default QualificationInstitutions