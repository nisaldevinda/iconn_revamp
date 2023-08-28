
import React, { useState, useEffect } from 'react'
import { getModel, Models } from '@/services/model';
import BasicContainer from '@/components/BasicContainer';

import {
    getAllDepartment,
    addDepartment,
    updateDepartment,
    removeDepartment
} from '@/services/department'



const Department: React.FC = () => {

    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.Department).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })

    return (
      <BasicContainer
        rowId="id"
        titleKey="department"
        defaultTitle="Department"
        model={model}
        tableColumns={[
          { name: 'name', sortable: true, filterable: true },
          { name: 'parentDepartment', sortable: true, filterable: true },
          { name: 'headOfDepartment', sortable: true, filterable: true },
        ]}
        recordActions={['add', 'edit', 'delete']}
        defaultSortableField={{ fildName: 'name', mode: 'ascend' }}
        searchFields={['name']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={getAllDepartment}
        addFunction={addDepartment}
        editFunction={updateDepartment}
        deleteFunction={removeDepartment}
        permissions={{
          addPermission: 'master-data-write',
          editPermission: 'master-data-write',
          deletePermission: 'master-data-write',
          readPermission: 'master-data-write',
        }}
      />
    );
}

export default Department