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
            <Access
                accessible={hasPermitted('my-attendance')}
                fallback={<PermissionDeniedPage />}
            >
                <div>
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
