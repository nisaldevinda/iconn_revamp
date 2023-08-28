import React from 'react'
import { PageContainer } from '@ant-design/pro-layout';
import WorkflowRequests from '@/components/WorkflowRequests';
import { Access, useAccess } from 'umi';
import PermissionDeniedPage from '../403';

const WorkflowInstance: React.FC = () => {
    const access = useAccess();
    const { hasAnyPermission } = access;

    return (
        <PageContainer>
            <Access
                accessible={hasAnyPermission([
                    'my-info-request',
                    'my-leave-request',
                    'my-leaves',
                    'my-attendance',
                    'my-info-request',
                    'my-resignation-request',
                    'my-claim-request',
                    'my-post-ot-request'
                ])}
                fallback={<PermissionDeniedPage />}
            >
                <WorkflowRequests pageType={"myRequests"} />
            </Access>
        </PageContainer>
    )
}

export default WorkflowInstance