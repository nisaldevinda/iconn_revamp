

import React, { useState, useEffect } from 'react'
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';
import {
    getAllConfirmationReasons,
    addConfirmationReasons,
    updateConfirmationReasons,
    removeConfirmationReasons
} from '@/services/confirmationReasons'

const ConfirmationReasons: React.FC = () => {

    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.ConfirmationReason).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })
    return (
      <BasicContainer
        rowId="id"
        titleKey="confirmation-reasons"
        defaultTitle="Confirmation Reasons"
        model={model}
        tableColumns={[{ name: 'name', sortable: true, filterable: true }]}
        recordActions={['add', 'edit', 'delete']}
        defaultSortableField={{ fildName: 'name', mode: 'ascend' }}
        searchFields={['name']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={getAllConfirmationReasons}
        addFunction={addConfirmationReasons}
        editFunction={updateConfirmationReasons}
        deleteFunction={removeConfirmationReasons}
        permissions={{
          addPermission: 'master-data-write',
          editPermission: 'master-data-write',
          deletePermission: 'master-data-write',
          readPermission: 'master-data-write',
        }}
      />
    );
}

export default ConfirmationReasons