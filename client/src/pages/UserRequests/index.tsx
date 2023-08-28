import React from 'react';
import { PageContainer } from '@ant-design/pro-layout';
import WorkflowRequests from '@/components/WorkflowRequests';

const WorkflowInstance: React.FC = () => {
  return (
    <PageContainer>
      <WorkflowRequests pageType={'allRequests'} />
    </PageContainer>
  );
};

export default WorkflowInstance;
