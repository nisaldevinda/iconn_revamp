import React, { useEffect, useState } from 'react';
import _ from 'lodash';
import { PageContainer } from '@ant-design/pro-layout';
import { history } from 'umi';
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';
import { deleteConfirmationProcess, getConfirmationProcessList } from '@/services/confirmationProcess';

const Notice: React.FC = () => {
  const [model, setModel] = useState<any>();

  useEffect(() => {
    if (_.isEmpty(model)) {
      getModel(Models.ResignationProcess).then((response) => {
        setModel(response.data);
      });
    }
  }, []);

  return (
    <PageContainer>
      <BasicContainer
        rowId="id"
        titleKey="resignationProcessConfiguration"
        defaultTitle="Confirmation Process Configuration"
        model={model}
        tableColumns={[{ name: 'name', filterable: true, sortable: true }]}
        recordActions={['add', 'edit', 'delete']}
        searchFields={['name']}
        addFormType="function"
        editFormType="function"
        getAllFunction={getConfirmationProcessList}
        addFunction={async () => history.push('/settings/config-confirmation-process/new')}
        editFunction={async (record) =>
          history.push(`/settings/config-confirmation-process/${record.id}`)
        }
        deleteFunction={deleteConfirmationProcess}
        permissions={{
          addPermission: 'config-confirmation-process-read-write',
          editPermission: 'config-confirmation-process-read-write',
          deletePermission: 'config-confirmation-process-read-write',
          readPermission: 'config-confirmation-process-read-write',
        }}
      />
    </PageContainer>
  );
};

export default Notice;
