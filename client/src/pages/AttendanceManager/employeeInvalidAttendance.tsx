import React from 'react';
import { Card, Col, Row } from 'antd';
import { PageContainer } from '@ant-design/pro-layout';
import ProCard from '@ant-design/pro-card';
import InvalidAttendanceTableView from '../../components/Attendance/InvalidAttendanceTableView';
import PermissionDeniedPage from '../403';
import { Access, useAccess } from 'umi';

const EmployeeInvalidAttendance: React.FC = () => {
    const access = useAccess();
    const { hasPermitted } = access;

    return (
        <>
            <Access
                accessible={hasPermitted('invalid-attendance-update-admin-access')}
                fallback={<PermissionDeniedPage />}
            >
                <div>
                    <PageContainer
                        header={{
                            ghost: true,
                        }}
                    >
                        <InvalidAttendanceTableView adminView={true} others={true} accessLevel={'admin'}/>
                    </PageContainer>
                </div>
            </Access>
        </>
    );
};

export default EmployeeInvalidAttendance;