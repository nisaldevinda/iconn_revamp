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
        accessible={hasPermitted('manager-leave-request-access')}
        fallback={<PermissionDeniedPage />}
      >
        <div
          style={{
            backgroundColor: 'white',
            borderTopLeftRadius: '30px',
            paddingLeft: '50px',
            paddingTop: '50px',
            paddingBottom: '50px',
            width: '100%',
            paddingRight: '0px',
          }}
        >
          <PageContainer>
            <TableView others={true} nonEditModel={true} accessLevel={'manager'} />
          </PageContainer>
        </div>
      </Access>
    </>
  );
};

export default ManagerLeaveRequest;
