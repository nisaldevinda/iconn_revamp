import React from 'react';
import { PageContainer } from '@ant-design/pro-layout';
import MyPostOtRequestTableView from './myPostOtRequestTableView';
import PermissionDeniedPage from '../403';
import { Access, useAccess } from 'umi';

const AttendanceAdmin: React.FC = () => {
  const access = useAccess();
  const { hasPermitted } = access;

  return (
    <>
      <Access
        accessible={hasPermitted('my-post-ot-request')}
        fallback={<PermissionDeniedPage />}
      >
        <div>
          <PageContainer
            header={{
              ghost: true,
            }}
          >
            <MyPostOtRequestTableView others={false} accessLevel={'employee'} />
          </PageContainer>
        </div>
      </Access>
    </>
  );
};

export default AttendanceAdmin;
