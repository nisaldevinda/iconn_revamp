import React from 'react';
import { Card, Col, Row } from 'antd';
import { PageContainer } from '@ant-design/pro-layout';
import ProCard from '@ant-design/pro-card';
import TableView from '../../components/Attendance/TableView';
import AttendanceManagerTableView from '../../components/Attendance/AttendanceManagerTableView';
import PermissionDeniedPage from '../403';
import { Access, useAccess } from 'umi';

const AttendanceManager: React.FC = () => {
    const access = useAccess();
    const { hasPermitted } = access;

    return (
      <>
        <Access
          accessible={hasPermitted('attendance-manager-access')}
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
              <AttendanceManagerTableView
                others={true}
                nonEditModel={true}
                accessLevel={'manager'}
              />
            </PageContainer>
          </div>
        </Access>
      </>
    );
};

export default AttendanceManager;
