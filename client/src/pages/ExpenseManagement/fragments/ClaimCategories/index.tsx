import React, { useState, useEffect } from 'react';
import { getModel, Models } from '@/services/model';
import BasicContainer from '@/components/BasicContainer';
import { Typography, Row } from 'antd';
import {
  claimCategories,
  addClaimCategory,
  updateClaimCategory,
  removeClaimCategory,
} from '@/services/expenseModule';

export type ClaimCategoryProps = {
  refresh?: number;
};

const Action: React.FC<ClaimCategoryProps> = (props) => {
  const { Text } = Typography;
  const [model, setModel] = useState<any>();
  const [refresh, setRefresh] = useState(0);
  useEffect(() => {
    if (!model) {
      getModel(Models.ClaimCategory).then((model) => {
        if (model && model.data) {
          setModel(model.data);
        }
      });
    }
  });

  useEffect(() => {
    setRefresh((prev) => prev + 1);
  }, [props.refresh]);

  return (
    <>
      <Row>
        <Text style={{ fontSize: 22, color: '#394241' }}>{'Claim Categories'}</Text>
      </Row>
      <BasicContainer
        rowId="id"
        titleKey="claimCategory"
        defaultTitle="Claim Categories"
        model={model}
        refresh={refresh}
        tableColumns={[
          { name: 'name', sortable: true, filterable: true },
          { name: 'description', sortable: true, filterable: true },
        ]}
        recordActions={['add', 'edit', 'delete']}
        searchFields={['name']}
        addFormType="model"
        editFormType="drawer"
        getAllFunction={claimCategories}
        addFunction={addClaimCategory}
        editFunction={updateClaimCategory}
        deleteFunction={removeClaimCategory}
        permissions={{
          addPermission: 'workflow-management-read-write',
          editPermission: 'workflow-management-read-write',
          deletePermission: 'workflow-management-read-write',
          readPermission: 'workflow-management-read-write',
        }}
      />
    </>
  );
};

export default Action;
