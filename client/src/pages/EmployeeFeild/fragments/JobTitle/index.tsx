import React, { useState, useEffect } from 'react'
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';

import {
    getAllJobTitles,
    addJobTitle,
    updateJobTitle,
    removeJobTitle
} from '@/services/jobTitle'



const JobTitle: React.FC = () => {

    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.JobTitle).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })


    return (
      <BasicContainer
        rowId="id"
        titleKey="job-title"
        defaultTitle="Job Title"
        model={model}
        tableColumns={[
          { name: 'name', sortable: true, filterable: true },
          { name: 'jobDescription' },
          { name: 'jobSpecification' },
          { name: 'notes' },
        ]}
        recordActions={['add', 'edit', 'delete']}
        defaultSortableField={{ fildName: 'name', mode: 'ascend' }}
        searchFields={['name']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={getAllJobTitles}
        addFunction={addJobTitle}
        editFunction={updateJobTitle}
        deleteFunction={removeJobTitle}
        permissions={{
          addPermission: 'master-data-write',
          editPermission: 'master-data-write',
          deletePermission: 'master-data-write',
          readPermission: 'master-data-write',
        }}
      />
    );
}

export default JobTitle