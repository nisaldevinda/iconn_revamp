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
      <div style={{ backgroundColor: '#F6F9FF', borderTopLeftRadius: '30px', padding: '50px' }}>
        <PageContainer>
          <WorkScheduleComponent monthlyView={false} service={workSchedule} />
        </PageContainer>
      </div>
    </Access>
  );
};


export default WorkSchedule;
