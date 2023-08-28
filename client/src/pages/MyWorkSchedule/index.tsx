import React from 'react';
import PermissionDeniedPage from '../403';
import { Access, useAccess, useIntl } from 'umi';
import { PageContainer } from '@ant-design/pro-layout';
import WorkScheduleComponent from '@/components/WorkScheduleComponent';
import workSchedule from '@/services/workSchedule';

const MyWorkSchedule = (props) => {
    const access = useAccess();
    const { hasPermitted } = access;

    return (

        <Access
            accessible={hasPermitted('my-work-schedule')}
            fallback={<PermissionDeniedPage />}
        >
            <PageContainer >
                <WorkScheduleComponent
                    editable={false}
                    monthlyView={true}
                    service={workSchedule}
                    isFromMyWorkSchedule={hasPermitted('my-shift-change-request')} />
            </PageContainer>
        </Access>
    );
}

export default MyWorkSchedule;