import React, { useState, useEffect } from 'react'
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';

import {
    getAllJobCategories,
    addJobCategories,
    updateJobCategories,
    removeJobCategories
} from '@/services/jobCategory'



const JobCategory: React.FC = () => {

    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel('jobCategory').then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })


    return (
      <BasicContainer
        rowId="id"
        titleKey="job-category"
        defaultTitle="Job Category"
        model={model}
        tableColumns={[
          { name: 'name', sortable: true, filterable: true },
        ]}
        recordActions={['add', 'edit', 'delete']}
        defaultSortableField={{ fildName: 'name', mode: 'ascend' }}
        searchFields={['name']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={getAllJobCategories}
        addFunction={addJobCategories}
        editFunction={updateJobCategories}
        deleteFunction={removeJobCategories}
        permissions={{
          addPermission: 'master-data-write',
          editPermission: 'master-data-write',
          deletePermission: 'master-data-write',
          readPermission: 'master-data-write',
        }}
      />
    );
}

export default JobCategory