

import React, { useState, useEffect } from 'react'
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';
import {
    getAllTransferTypes,
    addTransferTypes,
    updateTransferTypes,
    removeTransferTypes
} from '@/services/transferTypes'

const TransferTypes: React.FC = () => {

    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.TransferType).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })
    return (
      <BasicContainer
        rowId="id"
        titleKey="transfer-types"
        defaultTitle="Transfer Types"
        model={model}
        tableColumns={[{ name: 'name', sortable: true, filterable: true }]}
        recordActions={['add', 'edit', 'delete']}
        defaultSortableField={{ fildName: 'name', mode: 'ascend' }}
        searchFields={['name']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={getAllTransferTypes}
        addFunction={addTransferTypes}
        editFunction={updateTransferTypes}
        deleteFunction={removeTransferTypes}
        permissions={{
          addPermission: 'master-data-write',
          editPermission: 'master-data-write',
          deletePermission: 'master-data-write',
          readPermission: 'master-data-write',
        }}
      />
    );
}

export default TransferTypes