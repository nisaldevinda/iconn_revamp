import React, { useState, useEffect } from 'react'
import { getAllDynamicMasterData, addDynamicMasterData, updateDynamicMasterData, removeDynamicMasterData } from '@/services/dynamicMaster'
import { Spin } from 'antd';
import { getModel } from '@/services/model';
import BasicContainer from '@/components/BasicContainer';

interface DynamicMasterProps {
  modelName: string,
  modelTitle: string
}

const DynamicMaster: React.FC<DynamicMasterProps> = (props) => {
  const [loading, setLoading] = useState<boolean>(false);
  const [model, setModel] = useState<any>();
  const [tableColumns, setTableColumns] = useState<Array<any>>();

  useEffect(() => {
    refresh();
  }, [props])

  const refresh = async () => {
    setLoading(true);

    await getModel(props.modelName).then((model) => {
      if (model && model.data) {
        setModel(model.data)

        const fields = Object.values(model?.data?.modelDataDefinition?.fields) ?? [];
        setTableColumns(fields
          .filter((field: any) => field.isSystemValue != true)
          .map((field: any) => { return { name: field.name }; })
          .splice(0, 4));
      }
    });

    setLoading(false);
  }

  const getAllFunction = async (params?: any) => {
    return await getAllDynamicMasterData(props.modelName, params);
  }

  const addFunction = async (params: any) => {
    return await addDynamicMasterData(props.modelName, params);
  }

  const editFunction = async (params: any) => {
    return await updateDynamicMasterData(props.modelName, params);
  }

  const deleteFunction = async (record: any) => {
    return await removeDynamicMasterData(props.modelName, record);
  }

  return !loading
      ? <BasicContainer
        rowId="id"
        titleKey="dynamicMaster"
        defaultTitle={props.modelTitle}
        model={model}
        recordActions={['add', 'edit', 'delete']}
        tableColumns={tableColumns}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={getAllFunction}
        addFunction={addFunction}
        editFunction={editFunction}
        deleteFunction={deleteFunction}
        permissions={{
          addPermission: 'master-data-write',
          editPermission: 'master-data-write',
          deletePermission: 'master-data-write',
          readPermission: 'master-data-write',
        }}
      />
      : <Spin />
}

export default DynamicMaster