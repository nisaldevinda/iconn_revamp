import React, { useState, useEffect } from 'react'
import { getModel, Models } from '@/services/model';
import BasicContainer from '@/components/BasicContainer';
import {
    getAllLocations,
    addLocation,
    updateLocation,
    removeLocation
} from '@/services/location'


const Location: React.FC = () => {


    const [model, setModel] = useState<any>();
    useEffect(() => {
        if (!model) {
            getModel(Models.Location).then((model) => {
                if (model && model.data) {
                    setModel(model.data)
                }
            })
        }
    })


    return (
      <BasicContainer
        rowId="id"
        titleKey="location"
        defaultTitle="Location"
        model={model}
        tableColumns={[
          { name: 'name', sortable: true, filterable: true },
          { name: 'street1', sortable: true, filterable: true },
          { name: 'street2', sortable: true, filterable: true },
          { name: 'city', sortable: true, filterable: true },
          { name: 'country', sortable: true, filterable: true },
          { name: 'timeZone', sortable: true, filterable: true },
        ]}
        recordActions={['add', 'edit', 'delete']}
        defaultSortableField={{ fildName: 'name', mode: 'ascend' }}
        searchFields={['name']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={getAllLocations}
        addFunction={addLocation}
        editFunction={updateLocation}
        deleteFunction={removeLocation}
        permissions={{
          addPermission: 'master-data-write',
          editPermission: 'master-data-write',
          deletePermission: 'master-data-write',
          readPermission: 'master-data-write',
        }}
      />
    );
}

export default Location