import React from 'react';
import { PageContainer } from '@ant-design/pro-layout';
import TableView from '../../components/LeaveRequest/LeaveTableView';
import PermissionDeniedPage from '../403';
import { Access, useAccess } from 'umi';

const ManagerLeaveRequest: React.FC = () => {
  const access = useAccess();
  const { hasPermitted } = access;

  return (
    <>
      <Access
        accessible={hasPermitted('admin-leave-request-access')}
        fallback={<PermissionDeniedPage />}
      >
        <PageContainer>
          <TableView others={true} nonEditModel={true} accessLevel={'admin'} />
        </PageContainer>
      </Access>
    </>
  );
};

export default ManagerLeaveRequest;
