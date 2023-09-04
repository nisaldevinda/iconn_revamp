import React, { useState } from 'react';
import { PageContainer } from '@ant-design/pro-layout';
import AttendanceEmployeeTableView from '../../components/Attendance/AttendanceEmployeeTableView';
import PermissionDeniedPage from '../403';
import { Access, useAccess } from 'umi';

const AttendanceManager: React.FC = () => {
    const access = useAccess();
    const { hasPermitted } = access;

    return (
      <>
        <Access accessible={hasPermitted('my-attendance')} fallback={<PermissionDeniedPage />}>
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
            <PageContainer
              header={{
                ghost: true,
              }}
            >
              <AttendanceEmployeeTableView others={false} accessLevel={'employee'} />
            </PageContainer>
          </div>
        </Access>
      </>
    );
};

export default AttendanceManager;
