import React, { useState } from 'react';
import { PageContainer } from '@ant-design/pro-layout';
import SummaryView from '../../components/Attendance/SummaryView';
import PermissionDeniedPage from '../403';
import { Access, history, useAccess, useParams } from 'umi';

const AttendanceManager: React.FC = () => {
  const access = useAccess();
  const { hasPermitted } = access;

  const state = history.location.state;
  const employeeId = (state as any)?.employeeId;
  const datePassed = (state as any)?.summaryDate;
  const viewType = (state as any)?.viewType;

  return (
    <>
      <Access
        accessible={
          hasPermitted('attendance-employee-access') || hasPermitted('attendance-employee-summery')
        }
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
          <PageContainer
            header={{
              ghost: true,
            }}
          >
            <div style={{ height: '90%', paddingTop: 25 }}>
              <SummaryView datePassed={datePassed} employeeId={employeeId} viewType={viewType} />
            </div>
          </PageContainer>
        </div>
      </Access>
    </>
  );
};

export default AttendanceManager;
