import React, { useEffect, useState } from 'react';
import _ from 'lodash';
import { PageContainer } from '@ant-design/pro-layout';
import { history } from 'umi';
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';
import { deleteResignationProcess, getResignationProcessList } from '@/services/resignationProcess';

interface ResignationProcessConfigurationProps {
  hidePageContainer?: boolean
}

const ResignationProcessConfiguration: React.FC<ResignationProcessConfigurationProps> = (props) => {
  const [model, setModel] = useState<any>();

  useEffect(() => {
    if (_.isEmpty(model)) {
      getModel(Models.ResignationProcess).then((response) => {
        setModel(response.data);
      });
    }
  }, []);

  const mainContainer = <BasicContainer
    rowId="id"
    titleKey="resignationProcessConfiguration"
    defaultTitle="Resignation Process Configuration"
    model={model}
    tableColumns={[
      { name: 'name', filterable: true, sortable: true }
    ]}
    recordActions={['add', 'edit', 'delete']}
    searchFields={['name']}
    addFormType="function"
    editFormType="function"
    getAllFunction={getResignationProcessList}
    addFunction={async () => history.push('/settings/config-resignation-process/new')}
    editFunction={async (record) =>
      history.push(`/settings/config-resignation-process/${record.id}`)
    }
    deleteFunction={deleteResignationProcess}
    permissions={{
      addPermission: 'config-resignation-process-read-write',
      editPermission: 'config-resignation-process-read-write',
      deletePermission: 'config-resignation-process-read-write',
      readPermission: 'config-resignation-process-read-write',
    }}
  />

  return props.hidePageContainer ? mainContainer : <PageContainer>{mainContainer}</PageContainer>
};

export default ResignationProcessConfiguration;
