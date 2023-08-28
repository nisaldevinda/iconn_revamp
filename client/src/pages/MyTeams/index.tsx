import React, { useEffect, useState } from 'react';
import { getMyTeams } from '@/services/myTeams';
import _ from 'lodash';
import { PageContainer } from '@ant-design/pro-layout';
import BasicContainer from '@/components/BasicContainer';
import { getModel, Models } from '@/services/model';
import { history, useIntl } from 'umi';

const MyTeams: React.FC = () => {
  const intl = useIntl();
  const [model, setModel] = useState<any>();
  useEffect(() => {
    if (!model) {
      getModel(Models.Employee).then((model) => {
        if (model && model.data) {
          setModel(model.data);
        }
      });
    }
  });

  return (
    <PageContainer>
      <BasicContainer
        rowId="id"
        titleKey="my-teams"
        defaultTitle="My Teams"
        model={model}
        searchFields={['employeeNumber', 'employeeName']}
        tableColumns={[
          { name: 'employeeNumber' },
          { name: 'employeeName' },
          { name: 'currentJobs.jobTitle' },
          { name: 'currentJobs.location' },
          // { name: 'currentJobs.department' },
        ]}
        editFormType="function"
        addFormType="function"
        recordActions={['edit']}
        getAllFunction={getMyTeams}
        editFunction={async (record) => history.push(`/manager-self-service/my-teams/employee/${record.id}`)}
        permissions={{
          readPermission: 'my-teams-read',
          editPermission: 'my-teams-write'
        }}
      />
    </PageContainer>
  );
};

export default MyTeams;
