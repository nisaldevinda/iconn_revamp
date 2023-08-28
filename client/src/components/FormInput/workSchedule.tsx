
import PermissionDeniedPage from '@/pages/403';
import workSchedule from '@/services/workSchedule';
import React from 'react';
import { Access, useAccess } from 'umi';
import WorkScheduleComponent from '../WorkScheduleComponent';

export type workscheduleProps = {
    values: {}
};
const WorkSchedule: React.FC<workscheduleProps> = (props) => {
    const access = useAccess();
    const { hasPermitted } = access;

    return (
        <Access
            accessible={hasPermitted('work-schedule-read') || hasPermitted('work-schedule-read-write')}
            fallback={<PermissionDeniedPage />}
        >
            <WorkScheduleComponent
                editable={hasPermitted('work-schedule-read-write')}
                service={workSchedule}
                id={props.values.id}
                values={props.values}
                monthlyView={true} />
        </Access>
    );
}

export default WorkSchedule
