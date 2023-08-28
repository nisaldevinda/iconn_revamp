import React from 'react';
import { Button } from 'antd';
import { PageContainer } from '@ant-design/pro-layout';
import TableView from '../../components/LeaveRequest/LeaveTableView';
import PermissionDeniedPage from '../403';
import { Access, useAccess, history } from 'umi';
import { PlusOutlined } from '@ant-design/icons';

const EmployeeLeaveRequest: React.FC = () => {
  const access = useAccess();
  const { hasPermitted } = access;

  return (
    <>
      <Access
        accessible={hasPermitted('my-leaves')}
        fallback={<PermissionDeniedPage />}
      >
        <div>
          <PageContainer extra={[
            <Button
              key="3"
              onClick={(e) => {
                history.push('/ess/apply-leave');
              }}
              style={{
                background: '#FFFFFF',
                border: '1px solid #7DC014',
                color: '#7DC014',
              }}
            >
              {' '}
              <PlusOutlined /> Apply Leave
            </Button>,
          ]}>
            <TableView employeeId={1} others={false} accessLevel={'employee'} />
          </PageContainer>
        </div>
      </Access>
    </>
  );
};

export default EmployeeLeaveRequest;
