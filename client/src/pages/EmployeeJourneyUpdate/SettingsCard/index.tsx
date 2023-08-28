import React from 'react'
import EmployeeFeildSidebar from './components/sidebar'
import { PageContainer } from '@ant-design/pro-layout';
import { useAccess, Access } from 'umi';
import PermissionDeniedPage from './../../403';

const EmployeeFeilds: React.FC = () => {
  const access = useAccess();
  const { hasPermitted } = access;

  return (
    <Access accessible={hasPermitted('master-data-write')} fallback={<PermissionDeniedPage />}>
      <PageContainer>
        <EmployeeFeildSidebar />
      </PageContainer>
    </Access>
  );
}

export default EmployeeFeilds
