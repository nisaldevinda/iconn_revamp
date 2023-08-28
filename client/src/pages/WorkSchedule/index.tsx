import { PageContainer } from "@ant-design/pro-layout";
import { Access, useAccess } from "umi";
import PermissionDeniedPage from "../403";
import React, { useEffect, useState } from 'react';
import WorkScheduleComponent from "@/components/WorkScheduleComponent";
import workSchedule from "@/services/workSchedule";


const WorkSchedule: React.FC = () => {
  const access = useAccess();
  const { hasPermitted } = access;
  
  return (
    <Access
      accessible={hasPermitted('work-schedule-read-write')}
      fallback={<PermissionDeniedPage />}
    >
      <PageContainer>
        <WorkScheduleComponent
          monthlyView={false}
          service={workSchedule} />
      </PageContainer>
    </Access>
  )
};


export default WorkSchedule;
