import React from 'react';
import { Card, Col, Row } from 'antd';
import { PageContainer } from '@ant-design/pro-layout';
import ProCard from '@ant-design/pro-card';
import AttendanceAdminTableView from '../../components/Attendance/AttendanceAdminTableView';
import PermissionDeniedPage from '../403';
import { Access, useAccess } from 'umi';

const AttendanceAdmin: React.FC = () => {
    const access = useAccess();
    const { hasPermitted } = access;

    return (
      <>
        <Access
          accessible={hasPermitted('attendance-admin-access')}
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
              <AttendanceAdminTableView adminView={true} others={true} accessLevel={'admin'} />
            </PageContainer>
          </div>
        </Access>
      </>
    );
};

export default AttendanceAdmin;
