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
      <div
        style={{
          backgroundColor: 'white',
          borderTopLeftRadius: '30px',
          paddingLeft: '50px',
          paddingTop: '50px',
          width: '100%',
          paddingRight: '0px',
        }}
      >
        <PageContainer>
          <WorkScheduleComponent monthlyView={false} service={workSchedule} />
        </PageContainer>
      </div>
    </Access>
  );
};


export default WorkSchedule;
