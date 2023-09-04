import React from 'react';
import { PageContainer } from '@ant-design/pro-layout';
import WorkflowRequests from '@/components/WorkflowRequests';

const WorkflowInstance: React.FC = () => {
  return (
    <div
      style={{
        backgroundColor: 'white',
        borderTopLeftRadius: '30px',
        paddingLeft: '50px',
        paddingTop: '50px',
        paddingBottom: '50px',
        width: '100%',
        paddingRight: '0px',
      }}
    >
      <PageContainer>
        <WorkflowRequests pageType={'allRequests'} />
      </PageContainer>
    </div>
  );
};

export default WorkflowInstance;
