import React from 'react';
import { Tabs } from 'antd';
import workflowRoutes from '../workflowRoutes'
import { PageContainer } from '@ant-design/pro-layout';


const { TabPane } = Tabs

const WorkflowSidebar: React.FC = () => {


  if (workflowRoutes && workflowRoutes.length > 0) {
    return (
      <PageContainer style={{ background: 'white' }}>
      <Tabs defaultActiveKey="0" tabPosition={'left'} style={{ height: 900 }}>
        {
          workflowRoutes.map((items, key) => (
            <TabPane tab={items.name} key={key}>
              {items.component}
            </TabPane>
          ))
        }
      </Tabs>
      </PageContainer>

    )
  }

}

export default WorkflowSidebar

