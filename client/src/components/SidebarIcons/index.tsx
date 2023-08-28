import React from 'react';

import { ReactComponent as DocumentManager } from '../../assets/SideBar/documentManager.svg';
import { ReactComponent as Dashboard } from '../../assets/SideBar/dashboard.svg';
import { ReactComponent as Attendance } from '../../assets/SideBar/attendanceManager.svg';
import { ReactComponent as DocumentTemplate } from '../../assets/SideBar/documentTemplate.svg';
import { ReactComponent as EmailTemplate } from '../../assets/SideBar/emailTemplate.svg';
import { ReactComponent as Employee } from '../../assets/SideBar/employee.svg';
import { ReactComponent as EmployeeRequests } from '../../assets/SideBar/employeeRequests.svg';
import { ReactComponent as MyInfo } from '../../assets/SideBar/myInfo.svg';
import { ReactComponent as OrgCharts } from '../../assets/SideBar/orgCharts.svg';
import { ReactComponent as Reports } from '../../assets/SideBar/reports.svg';
import { ReactComponent as Settings } from '../../assets/SideBar/settings.svg';
import { ReactComponent as TeamInfo } from '../../assets/SideBar/teamInfo.svg';
import { ReactComponent as WorkPattern } from '../../assets/SideBar/workPattern.svg';
import { ReactComponent as WorkSchedule } from '../../assets/SideBar/workSchedule.svg';
import { ReactComponent as Leave } from '../../assets/SideBar/leave.svg';
import { ReactComponent as Notices } from '../../assets/SideBar/notices.svg';
import { ReactComponent as MyWorkSchedule } from '../../assets/SideBar/myWorkSchedule.svg';
import { ReactComponent as Payroll } from '../../assets/SideBar/payroll.svg';

const icons = {
  DocumentManager,
  Dashboard,
  Attendance,
  DocumentTemplate,
  EmailTemplate,
  Employee,
  EmployeeRequests,
  MyInfo,
  OrgCharts,
  Reports,
  Settings,
  TeamInfo,
  WorkPattern,
  WorkSchedule,
  Leave,
  Notices,
  MyWorkSchedule,
  Payroll
};

const SidebarIcons = (props) => {
  const SvgIcon = icons[props.icon];
  if (!SvgIcon) {
    return <></>;
  }
  return (
    <span
      style={{
        paddingRight: '16px',
        position: 'relative',
        top: '4px',
      }}
    >
      {' '}
      <SvgIcon width={20} height={20} />
    </span>
  );
};
export default SidebarIcons;
