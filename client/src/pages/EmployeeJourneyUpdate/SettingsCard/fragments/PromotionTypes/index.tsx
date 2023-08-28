

import React, { useState, useEffect } from 'react'
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';
import {
    getAllPromotionTypes,
    addPromotionTypes,
    updatePromotionTypes,
    removePromotionTypes
} from '@/services/promotionTypes'

const PromotionTypes: React.FC = () => {

    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.PromotionType).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })
    return (
      <BasicContainer
        rowId="id"
        titleKey="promotion-types"
        defaultTitle="Promotion Types"
        model={model}
        tableColumns={[{ name: 'name', sortable: true, filterable: true }]}
        recordActions={['add', 'edit', 'delete']}
        defaultSortableField={{ fildName: 'name', mode: 'ascend' }}
        searchFields={['name']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={getAllPromotionTypes}
        addFunction={addPromotionTypes}
        editFunction={updatePromotionTypes}
        deleteFunction={removePromotionTypes}
        permissions={{
          addPermission: 'master-data-write',
          editPermission: 'master-data-write',
          deletePermission: 'master-data-write',
          readPermission: 'master-data-write',
        }}
      />
    );
}

export default PromotionTypes